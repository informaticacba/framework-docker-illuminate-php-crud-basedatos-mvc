<?php namespace Database\Exception;

interface ExceptionHandlerInterface
{
    public function handle($query, array $bindings, \Exception $previousException);
}
