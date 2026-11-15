"""API sintética para testar o cURL PHP em loopback privado do container.

O processo é controlado por JSON em stdin/stdout. Somente certificados de teste,
portas locais, contagens e dados sintéticos atravessam esse canal de laboratório.
"""

from __future__ import annotations

import json
import socket
import ssl
import subprocess
import sys
import tempfile
import threading
import time
from base64 import b64decode
from hashlib import sha256
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from socketserver import BaseRequestHandler


class Servidor(ThreadingHTTPServer):
    daemon_threads = True
    request_queue_size = 32

    def handle_error(self, _pedido, _endereco):
        pass


class ConexaoSemHandshake(BaseRequestHandler):
    def handle(self):
        # Aceita TCP e não conclui TLS: exercita o prazo de conexão sem rede externa.
        time.sleep(0.6)


class Atendimento(BaseHTTPRequestHandler):
    protocol_version = "HTTP/1.1"

    def log_message(self, _formato, *_argumentos):
        pass

    def enviar(self, status=200, corpo=b"", cabecalhos=None):
        campos = dict(cabecalhos or {})
        if isinstance(corpo, (dict, list)):
            corpo = json.dumps(corpo, ensure_ascii=False).encode("utf-8")
            campos.setdefault("Content-Type", "application/json")
        elif isinstance(corpo, str):
            corpo = corpo.encode("utf-8")
        campos.setdefault("Content-Length", str(len(corpo)))
        self.cabecalhos(status, campos)
        if corpo:
            self.wfile.write(corpo)
            self.wfile.flush()

    def cabecalhos(self, status=200, campos=None):
        self.send_response(status)
        for nome, valor in (campos or {}).items():
            self.send_header(nome, str(valor))
        self.end_headers()
        self.wfile.flush()

    def parte(self, corpo):
        self.wfile.write(f"{len(corpo):x}\r\n".encode("ascii") + corpo + b"\r\n")
        self.wfile.flush()

    def desconectar(self):
        self.close_connection = True
        try:
            self.connection.shutdown(socket.SHUT_RDWR)
        except OSError:
            pass
        self.connection.close()

    def tratar(self):
        tamanho = int(self.headers.get("Content-Length", "0"))
        corpo = self.rfile.read(tamanho) if tamanho else b""
        dono = self.server.dono
        with dono.mutex:
            configuracao = dict(dono.configuracao)
        try:
            json_corpo = json.loads(corpo) if corpo and len(corpo) <= 65536 else None
        except (ValueError, UnicodeError):
            json_corpo = None
        with dono.mutex:
            dono.registros.append(
                {
                    "metodo": self.command,
                    "alvo": self.path,
                    "cabecalhos": {
                        nome.lower(): valor for nome, valor in self.headers.items()
                    },
                    "corpoTamanho": len(corpo),
                    "corpoSha256": sha256(corpo).hexdigest(),
                    "jsonCorpo": json_corpo,
                    "conexao": self.client_address[1],
                    "portaServidor": self.server.server_address[1],
                }
            )
        modo = configuracao.get("modo", "normal")
        try:
            if (
                self.path.endswith("/ok")
                or self.path.endswith("/aquecer")
                or self.path.endswith("/capturar")
            ):
                self.enviar(corpo={"id": "email-sintetico", "estado": "aceita"})
            elif modo == "queda" or modo == "quedaAquecida":
                self.desconectar()
            elif modo == "redirecionamento":
                self.enviar(
                    configuracao["status"],
                    "redirecionado",
                    {"Location": dono.destino + "/capturar"},
                )
            elif modo == "erro":
                mensagem = (
                    {
                        "erro": {
                            "codigo": "recusada",
                            "mensagem": "Pedido sintético recusado.",
                        }
                    }
                    if configuracao.get("json", True)
                    else "<html>detalhe-privado-sintetico</html>"
                )
                self.enviar(
                    configuracao["status"],
                    mensagem,
                    {
                        "Retry-After": "7",
                        "X-Request-Id": "requisicao-sintetica",
                        "Set-Cookie": "sessao=sintetica; Path=/",
                        "Authorization": "nao-retornar",
                        "Proxy-Authorization": "nao-retornar",
                    },
                )
            elif modo == "resposta":
                self.enviar(
                    configuracao.get("status", 200),
                    b64decode(configuracao.get("corpo", "")),
                    configuracao.get("cabecalhos"),
                )
            elif modo == "vazio":
                self.enviar(204 if self.path.endswith("/sem-conteudo") else 200)
            elif modo == "cookies":
                self.enviar(
                    corpo={"id": "sintetico"},
                    cabecalhos={
                        "Set-Cookie": "sessao=sintetica; Path=/",
                        "Authorization": "nao-retornar",
                        "Proxy-Authorization": "nao-retornar",
                        "X-Request-Id": "requisicao-sintetica",
                    },
                )
            elif modo == "cabecalhosGrandes":
                self.enviar(
                    corpo={"id": "sintetico"}, cabecalhos={"X-Grande": "a" * 70000}
                )
            elif modo == "truncada":
                dados = b64decode(configuracao["corpo"])
                if configuracao["moldura"] == "tamanho":
                    self.cabecalhos(
                        campos={"Content-Length": "500", "Connection": "close"}
                    )
                    self.wfile.write(dados)
                else:
                    self.cabecalhos(campos={"Transfer-Encoding": "chunked"})
                    self.wfile.write(b"1f4\r\n" + dados)
                self.wfile.flush()
                self.desconectar()
            elif modo == "fluxoGrande":
                self.cabecalhos(
                    campos={
                        "Transfer-Encoding": "chunked",
                        "Content-Type": "application/octet-stream",
                    }
                )
                dados = b"a" * 8192
                for _ in range(1024):
                    self.parte(dados)
                self.wfile.write(b"0\r\n\r\n")
                self.wfile.flush()
            elif modo == "fluxo":
                self.cabecalhos(
                    campos={
                        "Transfer-Encoding": "chunked",
                        "Content-Type": "application/octet-stream",
                    }
                )
                partes = configuracao.get("partes", ["YWJj", "ZGVm"])
                for indice, parte in enumerate(partes):
                    self.parte(b64decode(parte))
                    if configuracao.get("esperarConsumidor") and indice == 0:
                        if not dono.liberar.wait(2):
                            self.desconectar()
                            return
                    if configuracao.get("pausa"):
                        time.sleep(configuracao["pausa"])
                self.wfile.write(b"0\r\n\r\n")
                self.wfile.flush()
            else:
                self.enviar(corpo={"id": "email-sintetico", "estado": "aceita"})
        except (OSError, ssl.SSLError):
            self.close_connection = True

    do_GET = tratar
    do_POST = tratar
    do_PATCH = tratar
    do_PUT = tratar
    do_DELETE = tratar


class Laboratorio:
    def __init__(self):
        self.mutex = threading.Lock()
        self.configuracao = {}
        self.registros = []
        self.liberar = threading.Event()
        self.diretorio = tempfile.TemporaryDirectory(prefix="mailcore-php-tls-")
        self.certificado, self.chave = self.identidade("principal")
        self.outra_ca, _ = self.identidade("outra")
        self.servidores = []
        self.threads = []
        principal = self.criar_servidor(True)
        destino = self.criar_servidor(True)
        sem_tls = self.criar_servidor(False)
        parado = Servidor(("127.0.0.1", 0), ConexaoSemHandshake)
        thread_parado = threading.Thread(
            target=parado.serve_forever,
            kwargs={"poll_interval": 0.01},
            daemon=True,
        )
        thread_parado.start()
        self.servidores.append(parado)
        self.threads.append(thread_parado)
        self.base = f"https://127.0.0.1:{principal.server_address[1]}/v1"
        self.destino = f"https://127.0.0.1:{destino.server_address[1]}/v1"
        self.http = f"http://127.0.0.1:{sem_tls.server_address[1]}/v1"
        self.tls_parado = f"https://127.0.0.1:{parado.server_address[1]}/v1"

    def identidade(self, nome):
        certificado = Path(self.diretorio.name) / (nome + ".pem")
        chave = Path(self.diretorio.name) / (nome + ".key")
        subprocess.run(
            [
                "openssl",
                "req",
                "-x509",
                "-newkey",
                "rsa:2048",
                "-nodes",
                "-days",
                "1",
                "-subj",
                "/CN=API sintetica Mailcore",
                "-addext",
                "subjectAltName=IP:127.0.0.1",
                "-keyout",
                str(chave),
                "-out",
                str(certificado),
            ],
            check=True,
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
        )
        chave.chmod(0o600)
        return str(certificado), str(chave)

    def criar_servidor(self, tls):
        servidor = Servidor(("127.0.0.1", 0), Atendimento)
        servidor.dono = self
        if tls:
            contexto = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
            contexto.minimum_version = ssl.TLSVersion.TLSv1_2
            contexto.load_cert_chain(self.certificado, self.chave)
            servidor.socket = contexto.wrap_socket(servidor.socket, server_side=True)
        thread = threading.Thread(
            target=servidor.serve_forever, kwargs={"poll_interval": 0.01}, daemon=True
        )
        thread.start()
        self.servidores.append(servidor)
        self.threads.append(thread)
        return servidor

    def encerrar(self):
        self.liberar.set()
        for servidor in self.servidores:
            servidor.shutdown()
            servidor.server_close()
        for thread in self.threads:
            thread.join(2)
        self.diretorio.cleanup()

    def responder(self, comando):
        acao = comando["acao"]
        if acao == "configurar":
            with self.mutex:
                self.configuracao = comando.get("configuracao", {})
                self.registros = []
                self.liberar.clear()
            return {"ok": True}
        if acao == "registros":
            with self.mutex:
                return self.registros
        if acao == "liberar":
            self.liberar.set()
            return {"ok": True}
        raise RuntimeError("Comando de laboratório inválido.")


def publicar(valor):
    print(json.dumps(valor, ensure_ascii=False), flush=True)


def main():
    laboratorio = Laboratorio()
    publicar(
        {
            "base": laboratorio.base,
            "destino": laboratorio.destino,
            "http": laboratorio.http,
            "ca": laboratorio.certificado,
            "outraCa": laboratorio.outra_ca,
            "tlsParado": laboratorio.tls_parado,
        }
    )
    try:
        for linha in sys.stdin:
            comando = json.loads(linha)
            if comando["acao"] == "encerrar":
                publicar({"ok": True})
                break
            publicar(laboratorio.responder(comando))
    finally:
        laboratorio.encerrar()


if __name__ == "__main__":
    main()
