<?php
namespace Appe;

/**
 * Description of Magento
 *
 * @author Piotr Jaworski
 */
class Magento implements \Appe\EcommerceInterface
{
    public $channel = 'Magento2';
    
    private $curl;
    private $logger;
    private $bearer;

    public function __construct(\Curl\Curl $curl, \Appe\LoggerInterface $logger)
    {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->curl->setHeader('Content-Type', 'application/json');
        $this->curl->post('');
        $this->bearer = str_replace('"','', $this->curl->response);
        $this->logger->log('Auth token retrieved: '.$this->bearer);        
    }
    
    
    public function getData()
    {
        try {
            $this->logger->log('Getting data from Magento');     
            $this->curl->setHeader('Content-Type', 'application/json');
            $this->curl->setHeader('Accept', 'application/json');
            $this->curl->setHeader('Authorization', 'Bearer '.$this->bearer);
            $results = $this->curl->get('/rest/V1/orders?searchCriteria[pageSize]=50&searchCriteria[currentPage]=210')->response;
            $this->curl->close();   
            $this->logger->log('Data from Magento retrieved'); 
            return json_decode($results, true);
        } catch (\Exception $ex) {
            $this->logger->log('Data from Magento failed: '.$ex->getMessage()); 
            return false;
        }
    }
}
