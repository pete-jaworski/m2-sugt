<?php
namespace Appe;

 
class SubiektGT implements \Appe\ERPInterface
{
    const DB_TABLE = "Test_21_10_2017";
    const WAREHAUS_ID = 1;    
 
    private $subiektInstance;
    private $db;
    private $logger;
    
    public function __construct(\COM $com, \Appe\DatabaseInterface $db, \Appe\LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->db = $db;
        
        $gt = $com;   
        $gt->Autentykacja = 0; 
        $gt->Serwer = \Appe\MSSQL::DB_SERVER;
        $gt->Uzytkownik = "sa" ;
        $gt->UzytkownikHaslo = "superdupa";
        $gt->Baza = self::DB_TABLE;
        $gt->Operator = "Szef";
        $gt->OperatorHaslo = "";
        $this->subiektInstance = $gt->Uruchom(0, 4);                
        $this->subiektInstance->MagazynId = self::WAREHAUS_ID;
    }
    
    
    
    
    
    
    public function upload()
    {
        $orders = $this->db->read();
        
        $this->logger->log("Upload to SubiektGB started");
        if($orders){

            foreach($orders as $order){
                
                $error = false;
                $zk = $this->subiektInstance->SuDokumentyManager->DodajZK();
                $zk->NumerOryginalny = $order['orderid'];
                $zk->Rezerwacja = false;
                
                if(!$this->subiektInstance->KontrahenciManager->IstniejeWg($order['customer'], 3)){
                    if(!$this->addKontrahent($order)){
                        $error = true;
                    }
                }
                $zk->KontrahentId = $order['customer'];                    
                

                foreach(json_decode($order['items'], true) as $item){
                    if(!$this->subiektInstance->TowaryManager->IstniejeWg($item[0], 2)){
                        if(!$this->addTowar($item[0], $item[1])){
                            $error = true;
                        }                   
                    }
                    $pozycja = $zk->Pozycje->Dodaj($item[0]);
                    $pozycja->IloscJm = $item[1]['qty_ordered'];                    
                }
                
                if(!$error){
                    try {
                        $zk->Zapisz();
                        $this->logger->log("Added ZK ok");
                    } catch (\Exception $ex) {
                        $this->logger->log("Added ZK failed: ".$ex->getMessage());
                    } 
                    if($this->flagAsSorted($order)){
                        $this->logger->log("Database Record flagged as Sorted OK");
                    } else {
                        $this->logger->log("Database Record flagged as Sorted FAILED");
                    }
                }
                
                
            $this->logger->log("----------------------------");                
            
            }

        } else {
            $this->logger->log("Getting data from Database failed: Table empty");            
            return false;
        }
        
        return true;
    }
    
    




    
    private function flagAsSorted(array $order)
    {
        $this->db->connection->query("USE ". \Appe\MSSQL::DB_NAME);
        $markAsSorted = $this->db->connection->prepare("UPDATE orders set f2 = '".date('Y-m-d H:i:s')."' where id = ".$order['id']);
        return $markAsSorted->execute();
    }
 
    


    private function addTowar($key, array $value)
    {
        try {
            $towar = $this->subiektInstance->Towary->dodaj(1); 
            $towar->Aktywny = true;
            $towar->Symbol = $key;
            $towar->Nazwa = $this->fixEncoding(substr($value['name'], 0, 49));
            $towar->Ceny->Element(1)->Brutto  = $value['base_price'] > 0 ? $value['base_price'] : $value['parent_item']['base_price'];
            $towar->Ceny->Element(2)->Brutto  = $value['base_price'] != 0 ? $value['base_price'] : $value['parent_item']['base_price'];
            $towar->Zapisz();  
            $this->logger->log("Added TOWAR: ".$key." ok");
            return true;
        } catch (\Exception $ex) {
            $this->logger->log("Added TOWAR: ".$key." failed: ".$ex->getMessage());
            return false;            
        }

  }
    
    
    
    
    private function addKontrahent(array $order)
    {
        $kontrahentId = $order['customer'];
        $kontrahentData = json_decode($order['json'])->billing_address;

        if($kontrahentData){
            try {
                $kontrahent = $this->subiektInstance->Kontrahenci->dodaj();  
                $kontrahent->Nazwa = $this->fixEncoding(($kontrahentData->firstname.' '.$kontrahentData->lastname));
                $kontrahent->Symbol = $kontrahentId;
                $kontrahent->Ulica = $this->fixEncoding($kontrahentData->street[0]);
                $kontrahent->KodPocztowy = $this->fixEncoding($kontrahentData->postcode);
                $kontrahent->Miejscowosc = $this->fixEncoding($kontrahentData->city);
                $kontrahent->AdrDostNazwa = $this->fixEncoding(($kontrahentData->firstname.' '.$kontrahentData->lastname));
                $kontrahent->AdrDostUlica = $this->fixEncoding($kontrahentData->street[0]);
                $kontrahent->AdrDostKodPocztowy = $this->fixEncoding($kontrahentData->postcode);
                $kontrahent->AdrDostMiejscowosc = $this->fixEncoding($kontrahentData->city);
                $kontrahent->Email = $this->fixEncoding($kontrahentData->email);
                $kontrahent->Zapisz();          
                $this->logger->log("Added KONTRAHENT: ".$kontrahentId." ok");
                return true;
            } catch (\Exception $ex) {
                $this->logger->log("Added KONTRAHENT: ".$kontrahentId." failed: ".$ex->getMessage());
                return false;
            }            
        }    
    }
    
    


    private function fixEncoding($string)
    {
        return iconv("UTF-8", "ISO-8859-1", $string); 
    }
    
 
    
}
