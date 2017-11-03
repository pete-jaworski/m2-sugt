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
                
                $data = json_decode($order['json'], true);

                $error = false;
                $zk = $this->subiektInstance->SuDokumentyManager->DodajZK();
                $zk->NumerOryginalny = $data['NumerOryginalny'];
                $zk->Rezerwacja = false;
                
                if(!$this->subiektInstance->KontrahenciManager->IstniejeWg($data['Kontrahent']['symbol'], 3)){
                    if(!$this->addKontrahent($data['Kontrahent'])){
                        $error = true;
                    }
                }
                $zk->KontrahentId = $data['Kontrahent']['symbol'];                    
                

                foreach($data['Towary'] as $towar){
                    if(!$this->subiektInstance->TowaryManager->IstniejeWg($towar['symbol'], 2)){
                        if(!$this->addTowar($towar)){
                            $error = true;
                        }                   
                    }
                    $pozycja = $zk->Pozycje->Dodaj($towar['symbol']);
                    $pozycja->IloscJm = $towar['ilosc'];                    
                }
                

                if(!$error){
                    try {
                        $zk->Zapisz();
                        $this->logger->log("Added ZK ok");
                    } catch (\Exception $ex) {
                        $this->logger->log("Added ZK failed: ".$ex->getMessage());
                    } 
                    if($this->db->flagAsSorted($data)){
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
    
    





 
    


    private function addTowar(array $data)
    {
        if($data){
            try {
                $towar = $this->subiektInstance->Towary->dodaj(1); 
                $towar->Aktywny                     = $data['aktywny'];
                $towar->Symbol                      = $data['symbol'];
                $towar->Nazwa                       = $this->fixEncoding(substr($data['nazwa'], 0, 49));
                $towar->Ceny->Element(1)->Brutto    = round($data['ceny']['detaliczna']['brutto'], 2);
                $towar->Ceny->Element(2)->Brutto    = round($data['ceny']['hurtowa']['brutto'], 2);
                $towar->Zapisz();  
                $this->logger->log("Added TOWAR: ".$data['symbol']." ok");
                return true;
            } catch (\Exception $ex) {
                $this->logger->log("Added TOWAR: ".$data['symbol']." failed: ".$ex->getMessage());
                return false;            
            }
        }
  }
    
    
    
    
    private function addKontrahent(array $data)
    {
        if($data){
            try {
                $kontrahent = $this->subiektInstance->Kontrahenci->dodaj();  
                $kontrahent->Nazwa              = $this->fixEncoding($data['nazwa']);
                $kontrahent->Symbol             = $data['symbol'];
                $kontrahent->Ulica              = $this->fixEncoding($data['adresy']['siedziba']['ulica']);
                $kontrahent->KodPocztowy        = $this->fixEncoding($data['adresy']['siedziba']['kod']);
                $kontrahent->Miejscowosc        = $this->fixEncoding($data['adresy']['siedziba']['miejscowosc']);
                $kontrahent->Email              = $this->fixEncoding($data['email']);
                $kontrahent->AdrDostNazwa       = $this->fixEncoding($data['adresy']['dostawa']['nazwa']);
                $kontrahent->AdrDostUlica       = $this->fixEncoding($data['adresy']['dostawa']['ulica']);
                $kontrahent->AdrDostKodPocztowy = $this->fixEncoding($data['adresy']['dostawa']['kod']);
                $kontrahent->AdrDostMiejscowosc = $this->fixEncoding($data['adresy']['dostawa']['miejscowosc']);
                
                $kontrahent->Zapisz();          
                $this->logger->log("Added KONTRAHENT: ".$data['symbol']." ok");
                return true;
            } catch (\Exception $ex) {
                $this->logger->log("Added KONTRAHENT: ".$data['symbol']." failed: ".$ex->getMessage());
                return false;
            }            
        }    
    }
    
    


    private function fixEncoding($string)
    {
        return iconv("UTF-8", "ISO-8859-1//TRANSLIT//IGNORE", $string); 
    }
    
 
    
}
