<?php

declare(strict_types=1);

namespace Mailcore;

/** @implements \ArrayAccess<string|int, mixed> */
final class Resposta implements \ArrayAccess
{
    /** @param array<string, string> $cabecalhos */
    public function __construct(
        #[\SensitiveParameter] public readonly mixed $dados,
        public readonly int $status,
        #[\SensitiveParameter] public readonly array $cabecalhos = [],
    ) {
    }

    public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset) && !is_int($offset)) {
            return false;
        }
        return $this->dados instanceof \stdClass
            ? isset($this->dados->{(string) $offset})
            : (is_array($this->dados) && isset($this->dados[$offset]));
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (is_string($offset) || is_int($offset)) {
            if ($this->dados instanceof \stdClass && property_exists($this->dados, (string) $offset)) {
                return $this->dados->{(string) $offset};
            }
            if (is_array($this->dados) && array_key_exists($offset, $this->dados)) {
                return $this->dados[$offset];
            }
        }
        throw new \OutOfBoundsException('Campo ausente na resposta.');
    }

    public function offsetSet(mixed $offset, #[\SensitiveParameter] mixed $value): void
    {
        throw new \LogicException('A resposta oferece acesso somente de leitura.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('A resposta oferece acesso somente de leitura.');
    }

    /** @return array<string, int> */
    public function __debugInfo(): array
    {
        return ['status' => $this->status];
    }
}
