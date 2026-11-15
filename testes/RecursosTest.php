<?php

declare(strict_types=1);

namespace Mailcore\Testes;

use Mailcore\Excecoes\ErroConfiguracao;
use Mailcore\Recursos;
use Mailcore\Resposta;
use Mailcore\TransporteInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use stdClass;
use TypeError;

/** Contrato extraído por AST do SDK Node a234dc2, separado do gerador PHP. */
final class RecursosTest extends TestCase
{
    private const CONTRATOS = <<<'JSON'
[
{"classe":"ExecucoesAutomacoes","metodo":"listar","argumentos":["parte-0/ação+?@example.test",{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["automacaoId","filtros"],"verbo":"GET","caminho":"/automations/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/runs","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"ExecucoesAutomacoes","metodo":"obter","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["automacaoId","execucaoId"],"verbo":"GET","caminho":"/automations/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/runs/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Automacoes","metodo":"criar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/automations","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Automacoes","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["filtros"],"verbo":"GET","caminho":"/automations","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"Automacoes","metodo":"obter","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/automations/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Automacoes","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"PATCH","caminho":"/automations/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Automacoes","metodo":"remover","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"DELETE","caminho":"/automations/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Automacoes","metodo":"duplicar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"POST","caminho":"/automations/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/duplicate","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Automacoes","metodo":"parar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"POST","caminho":"/automations/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/stop","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"EmailsCaixa","metodo":"listar","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test",{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["caixaId","conversaId","paginacao"],"verbo":"GET","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/threads/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/emails","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"EmailsCaixa","metodo":"obter","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test","parte-2/ação+?@example.test"],"parametros":["caixaId","conversaId","emailId"],"verbo":"GET","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/threads/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/emails/parte-2%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"EmailsCaixa","metodo":"responder","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test","parte-2/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["caixaId","conversaId","emailId","pedido"],"verbo":"POST","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/threads/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/emails/parte-2%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/reply","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"EmailsCaixa","metodo":"encaminhar","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test","parte-2/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["caixaId","conversaId","emailId","pedido"],"verbo":"POST","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/threads/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/emails/parte-2%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/forward","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"EtiquetasConversaCaixa","metodo":"adicionar","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test",["etiqueta-a","etiqueta-b"]],"parametros":["caixaId","conversaId","ids"],"verbo":"POST","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/threads/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/labels","kwargs":{"corpo":{"ids":["etiqueta-a","etiqueta-b"]}}},
{"classe":"EtiquetasConversaCaixa","metodo":"substituir","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test",["etiqueta-a","etiqueta-b"]],"parametros":["caixaId","conversaId","ids"],"verbo":"PUT","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/threads/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/labels","kwargs":{"corpo":{"ids":["etiqueta-a","etiqueta-b"]}}},
{"classe":"EtiquetasConversaCaixa","metodo":"remover","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test","parte-2/ação+?@example.test"],"parametros":["caixaId","conversaId","etiquetaId"],"verbo":"DELETE","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/threads/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/labels/parte-2%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"ConversasCaixa","metodo":"listar","argumentos":["parte-0/ação+?@example.test",{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["caixaId","filtros"],"verbo":"GET","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/threads","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"ConversasCaixa","metodo":"buscar","argumentos":["parte-0/ação+?@example.test",{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["caixaId","filtros"],"verbo":"GET","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/threads/search","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"ConversasCaixa","metodo":"obter","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["caixaId","conversaId"],"verbo":"GET","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/threads/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"ConversasCaixa","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["caixaId","conversaId","pedido"],"verbo":"PATCH","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/threads/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"ConversasCaixa","metodo":"remover","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["caixaId","conversaId"],"verbo":"DELETE","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/threads/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"EtiquetasCaixa","metodo":"listar","argumentos":["parte-0/ação+?@example.test"],"parametros":["caixaId"],"verbo":"GET","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/labels","kwargs":{}},
{"classe":"EtiquetasCaixa","metodo":"obter","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["caixaId","etiquetaId"],"verbo":"GET","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/labels/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"EtiquetasCaixa","metodo":"criar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["caixaId","pedido"],"verbo":"POST","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/labels","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"EtiquetasCaixa","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["caixaId","etiquetaId","pedido"],"verbo":"PATCH","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/labels/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"EtiquetasCaixa","metodo":"remover","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["caixaId","etiquetaId"],"verbo":"DELETE","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/labels/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"RascunhosCaixa","metodo":"listar","argumentos":["parte-0/ação+?@example.test",{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["caixaId","paginacao"],"verbo":"GET","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/drafts","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"RascunhosCaixa","metodo":"obter","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["caixaId","rascunhoId"],"verbo":"GET","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/drafts/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"RascunhosCaixa","metodo":"criar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["caixaId","pedido"],"verbo":"POST","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/drafts","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"RascunhosCaixa","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["caixaId","rascunhoId","pedido"],"verbo":"PATCH","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/drafts/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"RascunhosCaixa","metodo":"remover","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["caixaId","rascunhoId"],"verbo":"DELETE","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/drafts/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"RascunhosCaixa","metodo":"enviar","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["caixaId","rascunhoId","pedido"],"verbo":"POST","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/drafts/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/send","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Caixas","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["filtros"],"verbo":"GET","caminho":"/inboxes","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"Caixas","metodo":"obter","argumentos":["parte-0/ação+?@example.test"],"parametros":["caixaId"],"verbo":"GET","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Caixas","metodo":"criar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/inboxes","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Caixas","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["caixaId","pedido"],"verbo":"PATCH","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Caixas","metodo":"remover","argumentos":["parte-0/ação+?@example.test"],"parametros":["caixaId"],"verbo":"DELETE","caminho":"/inboxes/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Campanhas","metodo":"criar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/broadcasts","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Campanhas","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["filtros"],"verbo":"GET","caminho":"/broadcasts","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"Campanhas","metodo":"obter","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/broadcasts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Campanhas","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"PATCH","caminho":"/broadcasts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Campanhas","metodo":"remover","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"DELETE","caminho":"/broadcasts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Campanhas","metodo":"duplicar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"POST","caminho":"/broadcasts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/duplicate","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Campanhas","metodo":"revisar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"POST","caminho":"/broadcasts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/review","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Campanhas","metodo":"enviar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"POST","caminho":"/broadcasts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/send","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Campanhas","metodo":"cancelar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"POST","caminho":"/broadcasts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/cancel","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Campanhas","metodo":"prever","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"POST","caminho":"/broadcasts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/preview","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Campanhas","metodo":"ler_previa","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["organizacaoId","previaId"],"verbo":"GET","caminho":"/broadcast-previews/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"formato":"texto"}},
{"classe":"Campanhas","metodo":"testar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"POST","caminho":"/broadcasts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Campanhas","metodo":"obter_metricas","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/broadcasts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/metrics","kwargs":{}},
{"classe":"Campanhas","metodo":"listar_destinatarios","argumentos":["parte-0/ação+?@example.test",{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["id","filtros"],"verbo":"GET","caminho":"/broadcasts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/recipients","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"Campanhas","metodo":"listar_links_clicados","argumentos":["parte-0/ação+?@example.test",{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["id","paginacao"],"verbo":"GET","caminho":"/broadcasts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/clicked-links","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"ChavesApi","metodo":"criar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["dados"],"verbo":"POST","caminho":"/api-keys","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"ChavesApi","metodo":"listar","argumentos":[],"parametros":[],"verbo":"GET","caminho":"/api-keys","kwargs":{}},
{"classe":"ChavesApi","metodo":"renomear","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","dados"],"verbo":"PATCH","caminho":"/api-keys/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"ChavesApi","metodo":"revogar","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"DELETE","caminho":"/api-keys/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"ConcessoesCli","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["paginacao"],"verbo":"GET","caminho":"/cli/oauth/grants","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"ConcessoesCli","metodo":"revogar","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"POST","caminho":"/cli/oauth/grants/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/revoke","kwargs":{}},
{"classe":"SegmentosContato","metodo":"listar","argumentos":["parte-0/ação+?@example.test"],"parametros":["contatoId"],"verbo":"GET","caminho":"/contacts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/segments","kwargs":{}},
{"classe":"SegmentosContato","metodo":"adicionar","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["contatoId","segmentoId"],"verbo":"POST","caminho":"/contacts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/segments","kwargs":{"aceitar_vazio":true,"corpo":{"segmento_id":"parte-1/ação+?@example.test"}}},
{"classe":"SegmentosContato","metodo":"remover","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["contatoId","segmentoId"],"verbo":"DELETE","caminho":"/contacts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/segments/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"TopicosContato","metodo":"listar","argumentos":["parte-0/ação+?@example.test"],"parametros":["contatoId"],"verbo":"GET","caminho":"/contacts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/topics","kwargs":{}},
{"classe":"TopicosContato","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test",[{"topico_id":"topico-a","assinatura":"opt_out"}]],"parametros":["contatoId","topicos"],"verbo":"PATCH","caminho":"/contacts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/topics","kwargs":{"corpo":{"topicos":[{"topico_id":"topico-a","assinatura":"opt_out"}]}}},
{"classe":"Contatos","metodo":"criar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/contacts","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Contatos","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["filtros"],"verbo":"GET","caminho":"/contacts","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"Contatos","metodo":"obter","argumentos":["parte-0/ação+?@example.test"],"parametros":["idOuEmail"],"verbo":"GET","caminho":"/contacts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Contatos","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["idOuEmail","pedido"],"verbo":"PATCH","caminho":"/contacts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Contatos","metodo":"remover","argumentos":["parte-0/ação+?@example.test"],"parametros":["idOuEmail"],"verbo":"DELETE","caminho":"/contacts/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Contatos","metodo":"adicionar_em_lote","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/contacts/batch","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Contatos","metodo":"alterar_em_lote","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/contacts/bulk","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"ExportacoesContatos","metodo":"criar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["filtros"],"verbo":"POST","caminho":"/contacts/exports","kwargs":{"corpo":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"ExportacoesContatos","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["consulta"],"verbo":"GET","caminho":"/contacts/exports","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"ExportacoesContatos","metodo":"obter","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/contacts/exports/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"ExportacoesContatos","metodo":"baixar","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/contacts/exports/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/download","kwargs":{"formato":"bytes"}},
{"classe":"ExportacoesContatos","metodo":"baixar_fluxo","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/contacts/exports/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/download","kwargs":{"formato":"fluxo"}},
{"classe":"ImportacoesContatos","metodo":"prever","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/contacts/imports/preview","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"ImportacoesContatos","metodo":"criar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/contacts/imports","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"ImportacoesContatos","metodo":"obter","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/contacts/imports/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Segmentos","metodo":"criar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/segments","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Segmentos","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["paginacao"],"verbo":"GET","caminho":"/segments","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"Segmentos","metodo":"obter","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/segments/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Segmentos","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"PATCH","caminho":"/segments/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Segmentos","metodo":"remover","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"DELETE","caminho":"/segments/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Topicos","metodo":"criar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/topics","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Topicos","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["paginacao"],"verbo":"GET","caminho":"/topics","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"Topicos","metodo":"obter","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/topics/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Topicos","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"PATCH","caminho":"/topics/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Topicos","metodo":"remover","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"DELETE","caminho":"/topics/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"PropriedadesContatos","metodo":"criar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/contact-properties","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"PropriedadesContatos","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["paginacao"],"verbo":"GET","caminho":"/contact-properties","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"PropriedadesContatos","metodo":"obter","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/contact-properties/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"PropriedadesContatos","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"PATCH","caminho":"/contact-properties/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"PropriedadesContatos","metodo":"remover","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"DELETE","caminho":"/contact-properties/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Dominios","metodo":"criar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["dados"],"verbo":"POST","caminho":"/domains","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Dominios","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["consulta"],"verbo":"GET","caminho":"/domains","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"Dominios","metodo":"obter","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/domains/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Dominios","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","dados"],"verbo":"PATCH","caminho":"/domains/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Dominios","metodo":"registros_dns","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/domains/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/dns","kwargs":{}},
{"classe":"Dominios","metodo":"verificar","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"POST","caminho":"/domains/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/verify","kwargs":{}},
{"classe":"Dominios","metodo":"remover","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"DELETE","caminho":"/domains/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Emails","metodo":"enviar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["dados"],"verbo":"POST","caminho":"/emails","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Emails","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["consulta"],"verbo":"GET","caminho":"/emails","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"Emails","metodo":"obter","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/emails/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Emails","metodo":"reagendar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","dados"],"verbo":"PATCH","caminho":"/emails/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Emails","metodo":"cancelar","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"POST","caminho":"/emails/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/cancel","kwargs":{}},
{"classe":"Emails","metodo":"obter_por_idempotencia","argumentos":["parte-0/ação+?@example.test"],"parametros":["chave"],"verbo":"GET","caminho":"/emails/idempotency/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Emails","metodo":"eventos","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/emails/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/events","kwargs":{}},
{"classe":"Emails","metodo":"baixar_anexo","argumentos":["parte-0/ação+?@example.test",2],"parametros":["id","indice"],"verbo":"GET","caminho":"/emails/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/attachments/2","kwargs":{"formato":"bytes"}},
{"classe":"Lotes","metodo":"enviar","argumentos":[[{"texto":"ação","opcional":null,"ativo":false}]],"parametros":["dados"],"verbo":"POST","caminho":"/emails/batch","kwargs":{"corpo":[{"texto":"ação","opcional":null,"ativo":false}]}},
{"classe":"Lotes","metodo":"obter_por_idempotencia","argumentos":["parte-0/ação+?@example.test"],"parametros":["chave"],"verbo":"GET","caminho":"/emails/batch/idempotency/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Eventos","metodo":"criar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/events","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Eventos","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["paginacao"],"verbo":"GET","caminho":"/events","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"Eventos","metodo":"obter","argumentos":["parte-0/ação+?@example.test"],"parametros":["idOuNome"],"verbo":"GET","caminho":"/events/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Eventos","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["idOuNome","pedido"],"verbo":"PATCH","caminho":"/events/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Eventos","metodo":"remover","argumentos":["parte-0/ação+?@example.test"],"parametros":["idOuNome"],"verbo":"DELETE","caminho":"/events/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Eventos","metodo":"enviar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/events/send","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"VersoesModelo","metodo":"listar","argumentos":["parte-0/ação+?@example.test",{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["modeloId","consulta"],"verbo":"GET","caminho":"/templates/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/versions","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"VersoesModelo","metodo":"obter","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["modeloId","versaoId"],"verbo":"GET","caminho":"/templates/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/versions/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"VersoesModelo","metodo":"restaurar","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["modeloId","versaoId","pedido"],"verbo":"POST","caminho":"/templates/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/versions/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/restore","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"VersoesModelo","metodo":"prever","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["modeloId","versaoId","pedido"],"verbo":"POST","caminho":"/templates/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/versions/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/preview","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"PastasModelo","metodo":"listar","argumentos":[],"parametros":[],"verbo":"GET","caminho":"/template-folders","kwargs":{}},
{"classe":"PastasModelo","metodo":"criar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/template-folders","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"PastasModelo","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"PATCH","caminho":"/template-folders/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"PastasModelo","metodo":"remover","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"DELETE","caminho":"/template-folders/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Modelos","metodo":"prever","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"POST","caminho":"/templates/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/preview","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Modelos","metodo":"obter_previa","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["organizacaoId","previaId"],"verbo":"GET","caminho":"/template-previews/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"formato":"texto"}},
{"classe":"Modelos","metodo":"testar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"POST","caminho":"/templates/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Modelos","metodo":"criar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/templates","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Modelos","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["filtros"],"verbo":"GET","caminho":"/templates","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"Modelos","metodo":"obter","argumentos":["parte-0/ação+?@example.test"],"parametros":["idOuAlias"],"verbo":"GET","caminho":"/templates/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Modelos","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["idOuAlias","pedido"],"verbo":"PATCH","caminho":"/templates/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Modelos","metodo":"remover","argumentos":["parte-0/ação+?@example.test"],"parametros":["idOuAlias"],"verbo":"DELETE","caminho":"/templates/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Modelos","metodo":"publicar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["idOuAlias","pedido"],"verbo":"POST","caminho":"/templates/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/publish","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Modelos","metodo":"duplicar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["idOuAlias","pedido"],"verbo":"POST","caminho":"/templates/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/duplicate","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Recebimento","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["filtros"],"verbo":"GET","caminho":"/receiving","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"Recebimento","metodo":"obter","argumentos":["parte-0/ação+?@example.test",{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["id","formato"],"verbo":"GET","caminho":"/receiving/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"Recebimento","metodo":"listar_anexos","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/receiving/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/attachments","kwargs":{}},
{"classe":"Recebimento","metodo":"obter_anexo","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["id","anexoId"],"verbo":"GET","caminho":"/receiving/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/attachments/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Recebimento","metodo":"baixar_original","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/receiving/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/raw","kwargs":{"formato":"bytes"}},
{"classe":"Recebimento","metodo":"baixar_anexo","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["id","anexoId"],"verbo":"GET","caminho":"/receiving/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/attachments/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/download","kwargs":{"formato":"bytes"}},
{"classe":"Recebimento","metodo":"baixar_original_fluxo","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/receiving/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/raw","kwargs":{"formato":"fluxo"}},
{"classe":"Recebimento","metodo":"baixar_anexo_fluxo","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["id","anexoId"],"verbo":"GET","caminho":"/receiving/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/attachments/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/download","kwargs":{"formato":"fluxo"}},
{"classe":"EventosWebhook","metodo":"listar","argumentos":["parte-0/ação+?@example.test",{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["webhookId","paginacao"],"verbo":"GET","caminho":"/webhooks/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/events","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"EventosWebhook","metodo":"obter","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["webhookId","eventoId"],"verbo":"GET","caminho":"/webhooks/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/events/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"EventosWebhook","metodo":"listar_tentativas","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test",{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["webhookId","eventoId","paginacao"],"verbo":"GET","caminho":"/webhooks/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/events/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/attempts","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"EventosWebhook","metodo":"reenviar","argumentos":["parte-0/ação+?@example.test","parte-1/ação+?@example.test"],"parametros":["webhookId","eventoId"],"verbo":"POST","caminho":"/webhooks/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/events/parte-1%2Fa%C3%A7%C3%A3o%2B%3F%40example.test/replay","kwargs":{}},
{"classe":"Webhooks","metodo":"criar","argumentos":[{"texto":"ação","opcional":null,"ativo":false}],"parametros":["pedido"],"verbo":"POST","caminho":"/webhooks","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Webhooks","metodo":"listar","argumentos":[{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}],"parametros":["paginacao"],"verbo":"GET","caminho":"/webhooks","kwargs":{"consulta":{"limite":2,"antes":"cursor/ação","ativo":false,"opcional":null}}},
{"classe":"Webhooks","metodo":"obter","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"GET","caminho":"/webhooks/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}},
{"classe":"Webhooks","metodo":"atualizar","argumentos":["parte-0/ação+?@example.test",{"texto":"ação","opcional":null,"ativo":false}],"parametros":["id","pedido"],"verbo":"PATCH","caminho":"/webhooks/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{"corpo":{"texto":"ação","opcional":null,"ativo":false}}},
{"classe":"Webhooks","metodo":"remover","argumentos":["parte-0/ação+?@example.test"],"parametros":["id"],"verbo":"DELETE","caminho":"/webhooks/parte-0%2Fa%C3%A7%C3%A3o%2B%3F%40example.test","kwargs":{}}
]
JSON;

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function contratos(): iterable
    {
        $casos = json_decode(self::CONTRATOS, false, 512, JSON_THROW_ON_ERROR);
        foreach ($casos as $caso) {
            $argumentos = $caso->argumentos;
            foreach ($caso->parametros as $indice => $nome) {
                if (in_array($nome, ['filtros', 'paginacao', 'consulta', 'formato'], true)) {
                    $argumentos[$indice] = (array) $argumentos[$indice];
                }
            }
            $configuracao = (array) $caso->kwargs;
            if (isset($configuracao['consulta'])) {
                $configuracao['consulta'] = (array) $configuracao['consulta'];
            }
            if (array_key_exists('aceitar_vazio', $configuracao)) {
                $configuracao['aceitarVazio'] = $configuracao['aceitar_vazio'];
                unset($configuracao['aceitar_vazio']);
            }
            $metodo = self::camel($caso->metodo);
            yield $caso->classe . '.' . $metodo => [[
                'classe' => $caso->classe,
                'metodo' => $metodo,
                'argumentos' => $argumentos,
                'verbo' => $caso->verbo,
                'caminho' => $caso->caminho,
                'configuracao' => $configuracao,
            ]];
        }
    }

    #[DataProvider('contratos')]
    public function testContratoHttp(array $caso): void
    {
        $transporte = new TransporteColetorRecursos();
        $classe = 'Mailcore\\Recursos\\' . $caso['classe'];
        $recurso = new $classe($transporte);
        $argumentos = $caso['argumentos'];
        $originais = self::json($caso['argumentos']);
        $opcoes = [
            'chaveIdempotencia' => 'negocio-123',
            'tempoLimite' => 2.5,
            'limiteRespostaBytes' => 4096,
            'cabecalhos' => ['X-Correlacao' => 'sintetica'],
        ];
        $fluxo = ($caso['configuracao']['formato'] ?? null) === 'fluxo';
        $partes = [];
        if ($fluxo) {
            $argumentos[] = static function (string $parte) use (&$partes): void {
                $partes[] = $parte;
            };
        }
        $argumentos[] = $opcoes;
        $resposta = $recurso->{$caso['metodo']}(...$argumentos);
        self::assertSame($fluxo ? $transporte->retornoFluxo : $transporte->retorno, $resposta);
        self::assertCount(1, $transporte->chamadas);
        $esperado = array_replace([
            'metodo' => $caso['verbo'],
            'caminho' => $caso['caminho'],
            'corpo' => null,
            'consulta' => [],
            'formato' => 'json',
            'aceitarVazio' => false,
            'opcoes' => $opcoes,
            'consumidor' => $fluxo,
        ], $caso['configuracao']);
        $recebido = $transporte->chamadas[0];
        $corpoEsperado = $esperado['corpo'];
        unset($esperado['corpo'], $recebido['corpo']);
        self::assertSame($esperado, $recebido);
        self::assertSame(self::json($corpoEsperado), self::json($transporte->chamadas[0]['corpo']));
        self::assertSame($originais, self::json($caso['argumentos']));
        if ($fluxo) {
            self::assertSame(['abc', 'def'], $partes);
            self::assertSame(6, $resposta->dados);
        }
    }

    public function testTodosOsMetodosPublicosEstaoNoContrato(): void
    {
        $porClasse = [];
        foreach (self::contratos() as [$caso]) {
            $porClasse[$caso['classe']][] = $caso['metodo'];
        }
        self::assertCount(151, iterator_to_array(self::contratos()));
        foreach ($porClasse as $nome => $metodos) {
            $classe = new ReflectionClass('Mailcore\\Recursos\\' . $nome);
            $encontrados = [];
            foreach ($classe->getMethods(ReflectionMethod::IS_PUBLIC) as $metodo) {
                if ($metodo->getDeclaringClass()->getName() === $classe->getName() && !$metodo->isConstructor()) {
                    $encontrados[] = $metodo->getName();
                }
            }
            sort($metodos);
            sort($encontrados);
            self::assertSame($metodos, $encontrados, $nome);
        }
    }

    public function testFilhosCompartilhamTransporteEPropriedadesSaoReadonly(): void
    {
        $transporte = new TransporteColetorRecursos();
        $contatos = new Recursos\Contatos($transporte);
        $contatos->segmentos->listar('contato');
        $contatos->topicos->listar('contato');
        $contatos->importacoes->obter('importacao');
        $contatos->exportacoes->obter('exportacao');
        $modelos = new Recursos\Modelos($transporte);
        $modelos->versoes->obter('modelo', 'versao');
        $modelos->pastas->listar();
        (new Recursos\Webhooks($transporte))->eventos->obter('webhook', 'evento');
        (new Recursos\Automacoes($transporte))->execucoes->obter('automacao', 'execucao');
        $caixas = new Recursos\Caixas($transporte);
        $caixas->conversas->listar('caixa');
        $caixas->conversas->etiquetas->substituir('caixa', 'conversa', []);
        $caixas->conversas->emails->obter('caixa', 'conversa', 'email');
        $caixas->etiquetas->listar('caixa');
        $caixas->rascunhos->obter('caixa', 'rascunho');
        (new Recursos\CliMailcore($transporte))->concessoes->listar();
        self::assertCount(14, $transporte->chamadas);
        self::assertSame('{"ids":[]}', self::json($transporte->chamadas[9]['corpo']));
        foreach ([
            [$contatos, ['segmentos', 'topicos', 'importacoes', 'exportacoes']],
            [$modelos, ['versoes', 'pastas']],
            [$caixas, ['conversas', 'etiquetas', 'rascunhos']],
            [$caixas->conversas, ['etiquetas', 'emails']],
        ] as [$objeto, $propriedades]) {
            $reflexao = new ReflectionClass($objeto);
            foreach ($propriedades as $propriedade) {
                self::assertTrue($reflexao->getProperty($propriedade)->isReadOnly());
            }
        }
    }

    public function testObjetosVaziosMapasNumericosNullFalseEListasSaoPreservados(): void
    {
        $transporte = new TransporteColetorRecursos();
        $dados = [
            'evento' => 'compra.concluida',
            'email' => 'usuario@example.test',
            'dados' => (object) [
                'nulo' => null,
                'ativo' => false,
                'objetoVazio' => new stdClass(),
                'listaVazia' => [],
                'mapaNumerico' => (object) ['0' => 'email', '1' => 'primeiro_nome'],
            ],
        ];
        $antes = self::json($dados);
        (new Recursos\Eventos($transporte))->enviar($dados);
        $corpo = $transporte->chamadas[0]['corpo'];
        self::assertInstanceOf(stdClass::class, $corpo);
        self::assertNull($corpo->dados->nulo);
        self::assertFalse($corpo->dados->ativo);
        self::assertInstanceOf(stdClass::class, $corpo->dados->objetoVazio);
        self::assertSame([], $corpo->dados->listaVazia);
        self::assertSame('{"0":"email","1":"primeiro_nome"}', self::json($corpo->dados->mapaNumerico));
        self::assertSame($antes, self::json($dados));
        self::assertSame($antes, self::json($corpo));
    }

    public function testCorposOpcionaisUsamObjetosVaziosEConsultasUsamArraysVazios(): void
    {
        $transporte = new TransporteColetorRecursos();
        (new Recursos\Emails($transporte))->listar();
        (new Recursos\Recebimento($transporte))->obter('email');
        (new Recursos\ExportacoesContatos($transporte))->criar();
        (new Recursos\Modelos($transporte))->duplicar('modelo');
        (new Recursos\VersoesModelo($transporte))->prever('modelo', 'versao');
        (new Recursos\Campanhas($transporte))->prever('campanha');
        (new Recursos\Automacoes($transporte))->duplicar('automacao');
        (new Recursos\RascunhosCaixa($transporte))->criar('caixa');
        self::assertSame([], $transporte->chamadas[0]['consulta']);
        self::assertSame([], $transporte->chamadas[1]['consulta']);
        foreach (array_slice($transporte->chamadas, 2) as $chamada) {
            self::assertInstanceOf(stdClass::class, $chamada['corpo']);
            self::assertSame('{}', self::json($chamada['corpo']));
        }
        self::assertNotSame($transporte->chamadas[2]['corpo'], $transporte->chamadas[3]['corpo']);
    }

    public function testLotePermaneceListaDeObjetosERecusaArrayAssociativo(): void
    {
        $transporte = new TransporteColetorRecursos();
        $lotes = new Recursos\Lotes($transporte);
        $dados = [['remetente' => 'origem@example.test', 'texto' => 'Olá']];
        $antes = self::json($dados);
        $lotes->enviar($dados);
        self::assertIsArray($transporte->chamadas[0]['corpo']);
        self::assertTrue(array_is_list($transporte->chamadas[0]['corpo']));
        self::assertSame($antes, self::json($transporte->chamadas[0]['corpo']));
        self::assertSame($antes, self::json($dados));
        try {
            $lotes->enviar(['mensagem' => $dados[0]]);
            self::fail('Lote associativo deveria ser recusado.');
        } catch (ErroConfiguracao) {
            self::assertCount(1, $transporte->chamadas);
        }
    }

    public function testIdentificadoresInvalidosEIndiceNegativoNaoIniciamOperacao(): void
    {
        $transporte = new TransporteColetorRecursos();
        $emails = new Recursos\Emails($transporte);
        foreach (['', '.', '..', "linha\nquebra", "controle\0"] as $id) {
            try {
                $emails->obter($id);
                self::fail('Identificador inválido deveria ser recusado.');
            } catch (ErroConfiguracao) {
                self::assertCount(0, $transporte->chamadas);
            }
        }
        foreach ([-1, 1.5, '0', null, true, false] as $indice) {
            try {
                $emails->baixarAnexo('email', $indice);
                self::fail('Índice inválido deveria ser recusado.');
            } catch (ErroConfiguracao|TypeError) {
                self::assertCount(0, $transporte->chamadas);
            }
        }
    }

    public function testFluxosExigemConsumidorAntesDeChamarTransporte(): void
    {
        $transporte = new TransporteColetorRecursos();
        $recebimento = new Recursos\Recebimento($transporte);
        try {
            $recebimento->baixarOriginalFluxo('email', null);
            self::fail('Consumidor obrigatório deveria ser recusado.');
        } catch (TypeError) {
            self::assertCount(0, $transporte->chamadas);
        }
    }

    private static function camel(string $nome): string
    {
        $partes = explode('_', $nome);
        return array_shift($partes) . implode('', array_map(ucfirst(...), $partes));
    }

    private static function json(mixed $valor): string
    {
        return json_encode($valor, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

final class TransporteColetorRecursos implements TransporteInterface
{
    public array $chamadas = [];
    public readonly Resposta $retorno;
    public readonly Resposta $retornoFluxo;

    public function __construct()
    {
        $this->retorno = new Resposta((object) ['id' => 'sintetico'], 200);
        $this->retornoFluxo = new Resposta(6, 200);
    }

    public function executar(
        string $metodo,
        string $caminho,
        array|stdClass|null $corpo = null,
        array $consulta = [],
        string $formato = 'json',
        bool $aceitarVazio = false,
        array $opcoes = [],
        ?callable $consumidor = null,
    ): Resposta {
        $this->chamadas[] = [
            'metodo' => $metodo,
            'caminho' => $caminho,
            'corpo' => $corpo,
            'consulta' => $consulta,
            'formato' => $formato,
            'aceitarVazio' => $aceitarVazio,
            'opcoes' => $opcoes,
            'consumidor' => $consumidor !== null,
        ];
        if ($formato === 'fluxo') {
            if ($consumidor === null) {
                throw new \LogicException('O teste de fluxo exige um consumidor.');
            }
            $consumidor('abc');
            $consumidor('def');
            return $this->retornoFluxo;
        }
        return $this->retorno;
    }
}
