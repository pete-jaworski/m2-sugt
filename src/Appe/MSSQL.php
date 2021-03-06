<?php
namespace Appe;

class MSSQL implements \Appe\DatabaseInterface
{
    const DB_SERVER = 'DESKTOP-HP2DPHD\INSERTGT';
    const DB_USERNAME = '';
    const DB_PASSWORD = '';
    const DB_NAME = 'SubiektGT_Magento';
    const TB_NAME = 'orders_2';
    const DB_CONNECTION_STRING = 'sqlsrv:Server='.self::DB_SERVER.';Database='.self::DB_NAME;    

    public $connection;
    private $logger;

    
    public function __construct(\Appe\LoggerInterface $logger)
    {
        $this->logger = $logger;
        try {
            ini_set('mssql.charset', 'UTF-8');
            $this->connection = new \PDO(self::DB_CONNECTION_STRING, self::DB_USERNAME, self::DB_PASSWORD);
            $this->logger->log('Connection initialized');
            $this->createDB();
        } catch (\PDOException $e) {
            $this->logger->log('Connection failed: ' . $e->getMessage());
            die();
        }        
    }


    
    
    
    
    public function read()
    {
        $this->connection->query("USE ".self::DB_NAME);
        $orders = array();
            
            try {
                $query = $this->connection->prepare("SELECT * FROM ".self::TB_NAME." WHERE f2 = '';");
                $query->execute();      
                $orders = $query->fetchAll();   
                $this->logger->log('Database data retrieved');   
                return $orders;
            } catch (\Exception $ex) {
                $this->logger->log('Could not get data from Database '.$ex->getMessage());  
                return false;
            } 

    }




    
    
    
    public function write(array $orders, $channel = '')
    {
        $errors = 0;
        
        if(!is_array($orders) || !$orders){
            throw new Exception('That is not an Array');
        }
        

        $this->connection->query("USE ".self::DB_NAME);
        
            foreach($orders as $order){
                try {
                    $query = $this->connection->prepare("INSERT INTO ".self::TB_NAME." VALUES 
                        ('".date('Y-m-d H:i:s')."',
                        '".$channel."',
                        '".$order['NumerOryginalny']."',
                        '".json_encode($order, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG)."',
                        '',    
                        '',
                        '')");

                    $query->execute();                                
                    $this->logger->log('Order #'.$order['NumerOryginalny']." saved");                    

                } catch (\Exception $ex) {
                    $errors++;
                    $this->logger->log('Order #'.$order['NumerOryginalny']." failed: ".$ex->getMessage());   
                }
        }  
        return !$errors;
    }


    
  
    
    public function flagAsSorted(array $order)
    {
        $this->connection->query("USE ".self::DB_NAME);
        $markAsSorted = $this->connection->prepare("UPDATE ".self::TB_NAME." set f2 = '".date('Y-m-d H:i:s')."' where id = ".$order['NumerOryginalny']);
        return $markAsSorted->execute();
    }






    private function createDB()
    {
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        $isDBAlready = $this->connection->prepare("select * from sys.databases where name='".self::DB_NAME."'");
        $isDBAlready->execute();
        
        if($isDBAlready->rowCount()){
            $this->logger->log('Database found');
        } else {
            
            try {
                $this->connection->query("IF EXISTS(select * from sys.databases where name='".self::DB_NAME."') "
                                            ."DROP DATABASE ".self::DB_NAME." "
                                            ."CREATE DATABASE ".self::DB_NAME);

                $this->logger->log('Database created');
                $this->connection->query("USE ".self::DB_NAME);
                $this->connection->query("CREATE TABLE ".self::TB_NAME." (
                                            id INT NOT NULL IDENTITY(1,1) PRIMARY KEY,
                                            date DATETIME NOT NULL,
                                            channel VARCHAR(255),
                                            orderid VARCHAR(255) NOT NULL,
                                            json TEXT NOT NULL,
                                            f1 VARCHAR(255),
                                            f2 VARCHAR(255),
                                            f3 VARCHAR(255))");
                $this->logger->log('Database Table created');
            } catch (\Exception $ex) {
                $this->logger->log('Database creation failed: ' . $ex->getMessage());
            }                       
        }
    }
}
