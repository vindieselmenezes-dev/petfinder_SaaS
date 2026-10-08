<?php

declare(strict_types=1);

/**
 * Lançada quando um registro necessário ao fluxo (pet, prontuário, etc.)
 * não existe ou não pertence a quem está operando.
 */
final class RegistroNaoEncontradoException extends RuntimeException
{
}
