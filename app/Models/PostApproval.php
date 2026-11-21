<?php
namespace App\\Models;
use Illuminate\\Database\\Eloquent\\Model;
class PostApproval extends Model {protected $fillable=['social_post_id','version','fingerprint','approved_by','approved_at'];protected function casts():array{return ['approved_at'=>'datetime'];}}