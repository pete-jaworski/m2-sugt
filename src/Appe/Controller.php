<?php
namespace Appe;

class Controller
{
    private $db;
    private $ecommerce;
    private $erp;
    private $logger;
    
    public function __construct
    (
        \Appe\DatabaseInterface $db,
        \Appe\EcommerceInterface $ecommerce,
        \Appe\ERPInterface $erp,
        \Appe\Logger $logger
            
    )
    {
        $this->logger = $logger;
        $this->logger->log('Integration initialized');                
        $this->db = $db;
        $this->ecommerce = $ecommerce;
        $this->erp = $erp;
    }

    
    public function getData()
    {
        $results = $this->ecommerce->getData();
  
        if($results){
            if($this->db->write($results, $this->ecommerce->channel)){
                $this->logger->log('getData completed with no Errors');        
            } else {
                $this->logger->log('getData completed failing');        
            }
        }
        
    }

    
    public function uploadData()
    {
        if($this->erp->upload()){
            $this->logger->log('putData completed');        
        } else {
            $this->logger->log('getData completed failing');        
        }
    }
    
}
