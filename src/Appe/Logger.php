<?php
namespace Appe;

class Logger implements \Appe\LoggerInterface
{
    public function log($message)
    {
        echo "*** ".$message."\n";
    }
}
