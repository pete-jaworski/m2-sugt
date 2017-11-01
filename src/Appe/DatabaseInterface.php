<?php
namespace Appe;

interface DatabaseInterface
{
    public function read();
    public function write(array $orders);    
}
