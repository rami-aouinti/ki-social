<?php

namespace App\Contracts;

/**
 * @group AiContentGenerator
 */
interface AiContentGenerator { public function generate(array $idea):array; }