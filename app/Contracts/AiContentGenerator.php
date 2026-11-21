<?php
namespace App\\Contracts;
interface AiContentGenerator { public function generate(array $idea):array; }