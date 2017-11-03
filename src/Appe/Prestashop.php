<?php
namespace Appe;

/**
 * Description of Prestashop
 *
 * @author Piotr Jaworski
 */
class Prestashop implements \Appe\EcommerceInterface
{
    
    const CUSTOMER_PREFIX = 'KON-PRE-';
    const PRODUCT_PREFIX = 'PROD-PRE-';
    const ACCESS_TOKEN = 'DC7WX8US51AILF3K55AATQCMBWVC879Z';
    public $channel = 'Prestashop';
    private $curl;
    private $logger;


    public function __construct(\Curl\Curl $curl, \Appe\LoggerInterface $logger)
    {
        $this->curl = $curl;
        $this->logger = $logger;
        
    }
    
    
    public function getData()
    {
        try {
            $this->logger->log('Getting data from Prestashop');   
            $orders = new \SimpleXMLElement($this->curl->get('http://'.self::ACCESS_TOKEN.'@www.e-tygryski.pl/api/orders')->response);
            $data = array();
            
            if(!$orders){
                $this->logger->log('Data from Prestashop failed: empty data'); 
                return false;
            }
            
            foreach($orders->orders->order as $item)
            {
                $this->logger->log("Get order ".$item['id']); 
                
                $order = new \SimpleXMLElement($this->curl->get('http://'.self::ACCESS_TOKEN.'@www.e-tygryski.pl/api/orders/'.$item['id'])->response);
                $kontrahent = new \SimpleXMLElement($this->curl->get('http://'.self::ACCESS_TOKEN.'@www.e-tygryski.pl/api/customers/'.$order->order->id_customer)->response);
                $dostawa = new \SimpleXMLElement($this->curl->get('http://'.self::ACCESS_TOKEN.'@www.e-tygryski.pl/api/addresses/'.$order->order->id_address_delivery)->response);                
                $korespondencja = new \SimpleXMLElement($this->curl->get('http://'.self::ACCESS_TOKEN.'@www.e-tygryski.pl/api/addresses/'.$order->order->id_address_invoice)->response);                                
                
                $towary = array();
                foreach($order->order->associations->order_rows->order_row as $row){
                    $towary[] = array(
                            'symbol'    => (string)self::PRODUCT_PREFIX.$row->product_id,
                            'aktywny'   => true,
                            'rodzaj'    => 1,
                            'nazwa'     => (string)$row->product_name,
                            'ilosc'     => (string)$row->product_quantity,
                            'ceny'      => array(
                                'detaliczna'    =>   array('netto' => '', 'brutto' => (string)$row->unit_price_tax_incl, 'waluta' => 'PLN'),
                                'hurtowa'       =>   array('netto' => '', 'brutto' => (string)$row->unit_price_tax_incl, 'waluta' => 'PLN'),
                                'specjalna'     =>   array('netto' => '', 'brutto' => (string)$row->unit_price_tax_incl, 'waluta' => 'PLN'),                
                            )

                        );
                }                      
                    
                
                $data[] = array(
                    'NumerOryginalny'   => (string)$item['id'],
                    'Rezerwacja'        => true,
                    'Kontrahent' => array(
                        'symbol'    => (string)self::CUSTOMER_PREFIX.$kontrahent->customer->id,
                        'nazwa'     => (string)$kontrahent->customer->firstname." ".$kontrahent->customer->lastname,
                        'email'     => (string)$kontrahent->customer->email,
                        'adresy'     => array(
                            'siedziba'  => array(
                                'miejscowosc'   => (string)$dostawa->address->city,
                                'kod'           => (string)$dostawa->address->postcode,
                                'ulica'         => (string)$dostawa->address->address1,
                                'numer'         => ''
                            ),
                            'korespondencja'  => array(
                                'nazwa'         => (string)$korespondencja->address->firstname." ".$korespondencja->address->lastname,
                                'miejscowosc'   => (string)$korespondencja->address->city,
                                'kod'           => (string)$korespondencja->address->postcode,
                                'ulica'         => (string)$korespondencja->address->address1,
                                'numer'         => ''
                            ),                 
                            'dostawa'  => array(
                                'nazwa'         => (string)$korespondencja->address->firstname." ".$korespondencja->address->lastname,
                                'miejscowosc'   => (string)$korespondencja->address->city,
                                'kod'           => (string)$korespondencja->address->postcode,
                                'ulica'         => (string)$korespondencja->address->address1,
                                'numer'         => ''
                            )                
                        )                         
                    ),
                    'Towary'    => $towary 
                );                      
            }
            
            $this->logger->log("Get order ".$item['id']);
            $this->curl->close(); 
            $this->logger->log('Data from Prestashop retrieved'); 
            return $data;
            
        } catch (\Exception $ex) {
            $this->logger->log('Data from Prestashop failed: '.$ex->getMessage()); 
            return false;
        }
    }
 
}
