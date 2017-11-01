<?php
namespace Appe;

/**
 * Description of Prestashop
 *
 * @author Piotr Jaworski
 */
class Prestashop implements \Appe\EcommerceInterface
{
    public $channel = 'Prestashop';
    
    private $curl;
    private $logger;
    private $bearer;

    public function __construct(\Curl\Curl $curl, \Appe\LoggerInterface $logger)
    {
        $this->curl = $curl;
        $this->logger = $logger;
        
    }
    
    
    public function getData()
    {
        try {
            $this->logger->log('Getting data from Prestashop');   
            $results = $this->curl->get('http://DC7WX8US51AILF3K55AATQCMBWVC879Z@www.e-tygryski.pl/api/orders')->response;
      
            
            if(!$results){
                $this->logger->log('Data from Prestashop failed: empty data'); 
                return false;
            }
            
            $xml = new \SimpleXMLElement($results);

            foreach($xml->orders->order as $item)
            {
                echo $item['id']."\n";
                print_r($this->curl->get('http://DC7WX8US51AILF3K55AATQCMBWVC879Z@www.e-tygryski.pl/api/orders/'.$item['id'])->response);
                echo "==========================================\n";
            }
            
 
 
            exit();
            $this->curl->close(); 
            $this->logger->log('Data from Prestashop retrieved'); 
            return json_decode($results, true);
            
            
            
        } catch (\Exception $ex) {
            $this->logger->log('Data from Prestashop failed: '.$ex->getMessage()); 
            return false;
        }
    }
}
