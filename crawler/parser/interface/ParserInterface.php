<?php

namespace crawler\parser\interface;

/**
 * ParserInterface defines core extendable functionality for parsing process.
 */
interface ParserInterface
{
    public function run();
    public function parse(string $type, string $url, string $page);
    public function parseCatalog(string $url, string $page);
    public function parseWarnings(string $url);
    public function parseDetails();
}
