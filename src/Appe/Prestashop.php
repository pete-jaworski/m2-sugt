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
            $orders = new \SimpleXMLElement($this->curl->get('http://DC7WX8US51AILF3K55AATQCMBWVC879Z@www.e-tygryski.pl/api/orders')->response);
            $data = array();
            
  
            
            if(!$orders){
                $this->logger->log('Data from Prestashop failed: empty data'); 
                return false;
            }
            
            foreach($orders->orders->order as $item)
            {
                echo "Get order ".$item['id']."\n";
                
                $order = new \SimpleXMLElement($this->curl->get('http://DC7WX8US51AILF3K55AATQCMBWVC879Z@www.e-tygryski.pl/api/orders/'.$item['id'])->response);
                $kontrahent = new \SimpleXMLElement($this->curl->get('http://DC7WX8US51AILF3K55AATQCMBWVC879Z@www.e-tygryski.pl/api/customers/'.$order->order->id_customer)->response);
                $dostawa = new \SimpleXMLElement($this->curl->get('http://DC7WX8US51AILF3K55AATQCMBWVC879Z@www.e-tygryski.pl/api/addresses/'.$order->order->id_address_delivery)->response);                
                $korespondencja = new \SimpleXMLElement($this->curl->get('http://DC7WX8US51AILF3K55AATQCMBWVC879Z@www.e-tygryski.pl/api/addresses/'.$order->order->id_address_invoice)->response);                                
                
                $towary = array();
                foreach($order->order->associations->order_rows->order_row as $row){
                    $towary[] = array(
                            'symbol'    => (string)"PROD-PRE-".$row->product_id,
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
                        'symbol'    => (string)"KON-PRE-".$kontrahent->customer->id,
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
                                'nazwa'         => (string)$dostawa->address->firstname." ".$dostawa->address->lastname,
                                'miejscowosc'   => (string)$dostawa->address->city,
                                'kod'           => (string)$dostawa->address->postcode,
                                'ulica'         => (string)$dostawa->address->address1,
                                'numer'         => ''
                            ),                 
                            'dostawa'  => array(
                                'nazwa'         => (string)$dostawa->address->firstname." ".$dostawa->address->lastname,
                                'miejscowosc'   => (string)$dostawa->address->city,
                                'kod'           => (string)$dostawa->address->postcode,
                                'ulica'         => (string)$dostawa->address->address1,
                                'numer'         => ''
                            )                
                        )                         
                    ),
                    'Towary'    => $towary 
                );                      
            }
            
            
            print_r($data);
            exit();
            $this->curl->close(); 
            $this->logger->log('Data from Prestashop retrieved'); 
            //return json_decode($results, true);
            
            
            
        } catch (\Exception $ex) {
            $this->logger->log('Data from Prestashop failed: '.$ex->getMessage()); 
            return false;
        }
    }
    
    
//    $order = array(
//        'NumerOryginalny'   => null,
//        'Rezerwacja'        => null,
//        'kontrahent' => array(
//            'symbol'    => null,
//            'nazwa'     => null,
//            'email'     => null,
//            'adresy'     => array(
//                'siedziba'  => array(
//                    'miejscowosc'   => null,
//                    'kod'           => null,
//                    'ulica'         => null,
//                    'numer'         => null
//                ),
//                'korespondencja'  => array(
//                    'nazwa'         => null,
//                    'miejscowosc'   => null,
//                    'kod'           => null,
//                    'ulica'         => null,
//                    'numer'         => null
//                )                 
//                'dostawa'  => array(
//                    'nazwa'         => null,
//                    'miejscowosc'   => null,
//                    'kod'           => null,
//                    'ulica'         => null,
//                    'numer'         => null
//                )                
//            ) 
//        ),
//        'towary' => array(
//            array(
//                'symbol'    => null,
//                'aktywny'   => null,
//                'rodzaj'    => null,
//                'nazwa'     => null,
//                'ilosc'     => null,
//                'ceny'      => array(
//                    'detaliczna'    =>   array('netto' => null, 'brutto' => null, 'waluta' => null),
//                    'hurtowa'       =>   array('netto' => null, 'brutto' => null, 'waluta' => null),
//                    'specjalna'     =>   array('netto' => null, 'brutto' => null, 'waluta' => null),                    
//                )
//                
//            )
//        )
//        
//        
//        
//    );
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}
