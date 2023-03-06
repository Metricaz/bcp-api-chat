<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Models\Borrower;
use App\Models\BorrowerMeta;
use Illuminate\Support\Facades\Storage;

class ApiGate extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    private $base_url = '';
    
    private $data = [
        'username'=> '',
        'password'=> ''
    ];
    private $clientId = '';
    private $token ='';
    private $metas = [];
    private $jsonBase = [];
    
    private $proposal = '';
 
    public function __construct(){ 
        $this->base_url = env('BPC_URL','');
        $this->data['username'] = env('BPC_USERNAME','');
        $this->data['password'] = env('BPC_PASS','');
    }

    private function authenticate()
    {
        $file = 'token.json';
        if (Storage::disk('local')->exists($file)) {
            $time = Storage::lastModified($file);
            $diff = time() - $time;
            $fullDays    = floor($diff/(60*60*24));   
            $fullHours   = floor(($diff-($fullDays*60*60*24))/(60*60));   
            $fullMinutes = floor(($diff-($fullDays*60*60*24)-($fullHours*60*60))/60);    
            if($fullMinutes<10){
                $tokenJson = Storage::disk('local')->get($file);
                $tokenJson = json_decode($tokenJson,true);
                $this->clientId = $tokenJson['clientId'];
                $this->token = $tokenJson['token'];        
                return true;
            }
        } 
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])
            ->timeout(120)
            ->post($this->base_url.'auth',$this->data);
        } catch (Exception $e) {
            echo 'erro no auth'; 
            abort(503);
        } 
        if ($response->status()>=200 and $response->status()<300) {
            Storage::disk('local')->put($file,$response->body());
            $dados = $response->json();
            $this->clientId = $dados['clientId'];
            $this->token = $dados['token'];        
            return true;
        }
        return false;
    }

    public function teste()
    {


 
        exit();
        $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])
            ->timeout(120)
            ->post($this->base_url.'auth',$this->data);

        var_dump($response->body());
        exit();
    }

    public function auth()
    {
        $this->authenticate();
        return response()->json(
            array(
                'clientId' => $this->clientId,
                'token' => $this->token,
            )
        ,200);
    }

    private function borrowers($cpf)
    {
        if (empty($this->clientId) or empty($this->token)) {
            $this->authenticate();
        }
        
        try {
            $response = Http::withHeaders([
                'authorization' => 'Bearer '.$this->token,
                'Content-Type' => 'application/json'
            ])
            ->timeout(120)
            ->get($this->base_url.'borrowers?document='.$cpf);
        } catch (Exception $e) {
            return false;
        }
        if ($response->status()>=200 and $response->status()<300) {
            return $response->json();
        }
        return false;
    }

    public function borrower(Request $request)
    {
        $defaults = array(
            'name'=>'',
            'cpf'=>'',
        );
        $bodyContent = $request->getContent();
        if (empty($bodyContent)) {
            return response('body vazio',500);
        }
        $bodyContent = json_decode($bodyContent,true);
        if (is_null($bodyContent) or $bodyContent === FALSE) {
            return response('json com erro',500);
        }
        $inputs = array_merge($defaults, array_intersect_key($bodyContent,$defaults));
        if (empty($inputs['name']) or empty($inputs['cpf'])) {
            return response('name e cpf requeridos',500);
        } 
        $proposaId = null;
        $borrowerApi = $this->borrowers($inputs['cpf']);
        if (isset($borrowerApi[0]['proposalsId'][0]) and !empty($borrowerApi[0]['proposalsId'][0])) {
            $proposaId = $borrowerApi[0]['proposalsId'][0];
        }
        
        $borrower = Borrower::_updateOrCreate(
            ['cpf' => $inputs['cpf']],
            [
                'name' =>  $inputs['name'],
                'proposaId' =>  $proposaId
            ],
        );
        if (!empty($borrower)) {
            return response()->json($borrower,200);
        }
        return response('',500); 
    }

    private function getDomains($url, $cache = false)
    {
        if ($cache!==false) {
            if (Storage::disk('local')->exists($cache)) {
                return Storage::disk('local')->get($cache);
            } 
        }
        if (empty($this->clientId) or empty($this->token)) {
            $this->authenticate();
        } 
        try {
            $response = Http::withHeaders([
                'authorization' => 'Bearer '.$this->token,
                'Content-Type' => 'application/json'
            ])->get($this->base_url.$url);
        } catch (Exception $e) {
            exit('falha na api BPC');
        }
        $resp = $response->body();
        Storage::disk('local')->put($cache,$resp);
        return $resp;
    }

    public function domain($domain)
    {
        $cachefile = $domain.'.json';
        $resp = $this->getDomains('domains/'.$domain,$cachefile);
        $dados = json_decode($resp,true);
        $options = [
            'text' => '',
            'options' => [],
        ];
        if (!is_array($dados)) {
            return response('erro de chamada',500);
        }
        foreach ($dados as $d) {
            $options['options'][] = array(
                'text' => $d['description'],
                'type' => "text/plain",
                'value' => (string) $d['id'],
            ); 
        }

        return response()->json($options);
    }

    public function financialInstitution()
    {
        $cachefile = 'financialinstitution.json';
        $resp = $this->getDomains('financialInstitution',$cachefile);
        $dados = json_decode($resp,true);
        $options = [
            'text' => 'Selecione um Banco',
            'options' => [],
        ];
        if (!is_array($dados)) {
            return response('erro de chamada',500);
        }
        foreach ($dados as $d) {
            $options['options'][] = array(
                'text' => $d['code'].' - '.$d['name'],
                'type' => "text/plain",
                'value' => (string) $d['code'],
            ); 
        }

        return response()->json($options);
    }

    public function locations($cep)
    {
        if (empty($cep)) {
            return response('cep vazio',400);
        }
        if (empty($this->clientId) or empty($this->token)) {
            $this->authenticate();
        }
        $user_agent = 'Mozilla';
        try {
            $response = Http::withHeaders([
                'authorization' => 'Bearer '.$this->token,
                'user-agent' => $user_agent,
                'client-id' => $this->clientId,
            ])->get($this->base_url.'locations/addresses/'.$cep);
        } catch (Exception $e) {
            return response('falha na api BPC',500);
        } 

        
        if ($response->status()>=200 and $response->status()<300) {
            $endereco = $response->json();
            if (empty($endereco['city']) and !empty($endereco['streetAddress'])) {
                $endereco['city'] = $endereco['streetAddress'];
                $endereco['streetAddress'] = null;
            }

            return response()->json($endereco,200);
        }
        if ($response->status()>=400 and $response->status()<500) {
            return response()->json($response->json(),400);
        }
        return response($response->getBody(),500);
    }


    public function objectives()
    {
        $cachefile = 'objectives.json';
        $resp = $this->getDomains('domains/loan-objective',$cachefile);
        $dados = json_decode($resp,true);
        $options = [
            'text' => '',
            'options' => [], 
        ];
        foreach ($dados as $d) {
            $options['options'][] = array(
                'text' => $d['description'],
                'type' => "text/plain",
                'value' => (string) $d['id'],
            ); 
        }

        return response()->json($options);
    }

    public function professions(Request $request)
    {
        $inputs = $request->all();
        if (!isset($inputs['occupation'])) {
           exit('occupation vazio');
        }
        $occupation = $inputs['occupation']; 
        
        $cachefile = 'occupation-'.strtolower($occupation).'.json';
        $resp = $this->getDomains('domains/professions?occupationType='.$occupation,$cachefile);
        $dados = json_decode($resp,true);
        if (isset($dados['errors'])) {
            return response('',500); 
        }
        $options = [
            'text' => 'Selecione sua profissão',
            'options' => [],
        ];
        foreach ($dados as $d) {
            $options['options'][] = array(
                'text' => $d['description'],
                'type' => "text/plain",
                'value' => (string) $d['id'],
            ); 
        }
 
        return response()->json($options);
    }



     

    public function borrowerProposal(Request $request)
    {
        $bodyContent = $request->getContent();
        $gets = $request->all();
        if (empty($bodyContent) or empty($gets)) {
            exit('vazio');
        }

        $borrower = Borrower::updateOrCreate(
            ['cpf' => $gets['cpf']],
            ['proposal' =>  $bodyContent],
        );
        if (!empty($borrower)) {
            return response()->json($borrower,200);
        }
        return response('',500);

    }
    public function borrowerMetas(Request $request)
    {
        $defaults = array(
            'cpf'=>'',
            'fields'=>'',
        );
        $bodyContent = $request->getContent();
        if (empty($bodyContent)) {
            exit('falha');
        }
        $bodyContent = json_decode(utf8_encode($bodyContent),true);

        if (is_null($bodyContent) or $bodyContent === FALSE) {
            exit('json com erro');
        }

        $inputs = array_merge($defaults, array_intersect_key($bodyContent,$defaults));
        if (empty($inputs['fields']) or !is_array($inputs['fields']) or empty($inputs['cpf'])) {
            exit('inputs com erro');
        } 
         

        $borrower = Borrower::where('cpf',$inputs['cpf'])->first();
        if (empty($borrower)) {
            exit('cpf nao encontrado');
        }


        $borrowerMeta = ['error'=>[],'success'=>[]]; 
        foreach ($inputs['fields'][0] as $k => $v) {
            if ( $v===true) {
                 $v = 'true';
            }
            if ( $v===false) {
                 $v = 'false';
            }
            $borrowerMeta['success'][] = BorrowerMeta::_updateOrCreate(
                [
                    'id_borrowers' => $borrower->id,
                    'field' => $k,
                ],
                ['value' =>  $v],
            );
        } 
       
        return response()->json($borrowerMeta);
    }

    public function borrowerMeta(Request $request)
    {
        $defaults = array(
            'cpf'=>'',
            'field'=>'',
            'value'=>'',
        );
        $bodyContent = $request->getContent();
        if (empty($bodyContent)) {
            exit('falha');
        }
        $bodyContent = json_decode(utf8_encode($bodyContent),true);
        if (is_null($bodyContent) or $bodyContent === FALSE) {
            exit('json com erro');
        }
        $inputs = array_merge($defaults, array_intersect_key($bodyContent,$defaults));
        if (empty($inputs['name']) or empty($inputs['cpf'])) {
            return false;
        } 
        $inputs = array_merge($defaults, array_intersect_key($request->all(),$defaults));
        if (empty($inputs['field']) or empty($inputs['cpf']) or empty($inputs['value'])) {
            return false;
        }

        $borrower = Borrower::where('cpf',$inputs['cpf'])->first();
        if (empty($borrower)) {
            return false;
        }
       
        $borrowerMeta = BorrowerMeta::_updateOrCreate(
            [
                'id_borrowers' => $borrower->id,
                'field' => $inputs['field'],
            ],
            ['value' =>  $inputs['value']],
        );
        return true;
    }

    public function getTypedNumber($string)
    {
        if (is_bool($string)){
            return $string; 
        }
        if ($string=='true' or $string=='Sim'){
            return true; 
        }
        if ($string=='false' or $string=='Não'){
            return false; 
        }

        if (strlen($string)==10 and strpos($string, '/')!==FALSE ) {
            return $this->convertDate($string);
        }

        $pattern = '/^(\d{1,3})((,)(\d{3}))*((\.)(\d{1,2}))?$|^(\d{1,3})((\.)(\d{3}))*((,)(\d{1,2}))?$/';
        $replacement = '\1\8\4\11.\7\14';
        $number = preg_replace($pattern, $replacement,$string);
        if (is_numeric($number)){
            if (strpos($number, ".") === false )  return intval($number);
            else return floatval($number);
        }
        return $string;
    }

    public function walk_recursive(&$item, $key)
    {
        
        $beString = ['cpf','cnpj','value', 'areaCode','number','postalCode','branchNumber','accountNumber','accountNumberDigit','bankNumber','professionId','inssNumber'];
        
        if (in_array($key, $beString)) {
            $item = (string) $item;
        } else {
            $item = $this->getTypedNumber(trim($item));
        }
       
    }

    private function convertDate($data,$time = true){

        $data = implode("-",array_reverse(explode("/",$data)));
        if ($time) {
            $data .= 'T00:00:00.000Z';
        }
        return $data;
    }

    public function proposals(Request $request)
    {


        $inputs = $request->all();
        if (empty($inputs['cpf'])) {
            return response('cpf vazio',400);
        }
        
        $borrower = Borrower::where('cpf',$inputs['cpf'])->first();
        if (empty($borrower)) {
            return response('CPF não encontrado',400);
        }
        if (!isset($borrower->proposal) or empty($borrower->proposal)) {
            exit('proposta deste usuario nao encontrada');
        }

        if (empty($this->clientId) or empty($this->token)) {
            $this->authenticate();
        }

        $user_agent = 'Mozilla';
        $this->proposal = json_decode($borrower->proposal, true);
        if ($this->proposal['borrower']['document']['type'] != '8') {
            unset($this->proposal['borrower']['document']['validUntil']);
        }

        array_walk_recursive($this->proposal , [$this , 'walk_recursive']);

    
        try {
            $response = Http::withHeaders([
                'authorization' => 'Bearer '.$this->token,
                'user-agent' => $user_agent,
                'client-id' => $this->clientId,
            ])->post($this->base_url.'proposals',$this->proposal);
        } catch (Exception $e) {
            return response('falha na api BPC',500);
        } 
        
        if ($response->status()>=200 and $response->status()<300) {
            $proposta = $response->json();
            $borrower = Borrower::updateOrCreate(
                ['cpf' => $inputs['cpf']],
                ['proposaId' =>  $proposta['id']],
            );
            return response($proposta['id'],200);
        }
        if ($response->status()>=400 and $response->status()<500) {
            return response()->json($response->json(),400);
        }
        return response($response->getBody(),500);
     }

    /*public function proposals(Request $request)
    {
        $inputs = $request->all();
        if (empty($inputs['cpf'])) {
            return false;
        }

        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $json = Storage::disk('local')->get('proposal.json');
        $this->jsonBase = json_decode($json, true);
        $borrower = Borrower::where('cpf',$inputs['cpf'])->first();
        if (empty($borrower)) {
            return false;
        }
        $borrowerMeta = BorrowerMeta::where('id_borrowers',$borrower->id)->get();
        $this->metas['name'] = $borrower->name;
        $this->metas['cpf'] = $borrower->cpf;
        
        foreach ($borrowerMeta as $meta) {
            $this->metas[$meta->field] = $meta->value;
        }

        array_walk_recursive($this->jsonBase, [$this , 'walk_recursive']);

        if (empty($this->clientId) or empty($this->token)) {
            $this->authenticate();
        }

        try {
            $response = Http::withHeaders([
                'authorization' => 'Bearer '.$this->token,
                'user-agent' => $user_agent,
                'client-id' => $this->clientId,
            ])->post($this->base_url.'proposals',$this->jsonBase);
        } catch (Exception $e) {
            abort(503);
        } 
        var_dump($this->jsonBase);
        var_dump((string) $response->getBody());
    
        return '';
    }*/

}
