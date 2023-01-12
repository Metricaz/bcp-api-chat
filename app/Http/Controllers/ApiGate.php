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
    private $base_url = 'https://api.stg.bompracredito.com.br/';
    private $data = [
        'username'=>'adminqa',
        'password'=>'AdminQA@2022'
    ];
    private $clientId = '';
    private $token ='';
    private $metas = [];
    private $jsonBase = [];

    public function __construct()
    {
    }

    private function authenticate()
    {
        try {
            $response = Http::post($this->base_url.'auth',$this->data);
        } catch (Exception $e) {
            abort(503);
        } 

        $dados = $response->json();
        $request = new Request;
        $this->clientId = $dados['clientId'];
        $this->token = $dados['token'];

        
        return true;
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

    public function objectives()
    {
        $cachefile = 'objectives.json';
        $resp = $this->getDomains('domains/loan-objective',$cachefile);
        $dados = json_decode($resp,true);
        $options = [
            'text' => 'Selecione o motivo do Empréstimo',
            'options' => [],
        ];
        foreach ($dados as $d) {
            $options['options'][]['text'] = $d['description'];
        }
 
        return response()->json($options);
    }

    public function professions(Request $request)
    {
        $inputs = $request->all();
        if (!isset($inputs['occupation']) or empty($inputs['occupation'])) {
           exit('occupation vazio');
        }
        $occupations = [
            'Assalariado' => 'WageWorker',
            'Funcionário Público' => 'PublicWorker',
            'Aposentado ou Pensionista' => 'RetiredOrPensioner',
            'Autônomo' => 'Autonomous',
            'Profissional Liberal' => 'LiberalWorker',
            'Empresário' => 'CompanyOwner',
            'Estudante' => 'Student',
        ];
        if (!isset($occupations[$inputs['occupation']])) {
           exit('occupation nao encontrado');
        }
        $occupation = $occupations[$inputs['occupation']];
        
        $cachefile = 'occupation-'.strtolower($occupation).'.json';
        $resp = $this->getDomains('domains/professions?occupationType='.$occupation,$cachefile);
        $dados = json_decode($resp,true);
        $options = [
            'text' => 'Selecione sua profissão (beta)',
            'options' => [],
        ];
        foreach ($dados as $d) {
            $options['options'][]['text'] = $d['description'];
        }
 
        return response()->json($options);
    }



    public function borrower(Request $request)
    {
        $defaults = array(
            'name'=>'',
            'cpf'=>'',
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
        $borrower = Borrower::_firstOrCreate(
            ['cpf' => $inputs['cpf']],
            ['name' =>  $inputs['name']],
        );
        return response()->json($borrower);
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
        return response()->json($borrower);

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
        if ($string===TRUE or $string===FALSE){
            return $string; 
        }
        if ($string=='true'){
            return true; 
        }
        if ($string=='false'){
            return false; 
        }
        $pattern = '/^(\d{1,3})((,)(\d{3}))*((\.)(\d{1,2}))?$|^(\d{1,3})((\.)(\d{3}))*((,)(\d{1,2}))?$/';
        $replacement = '\1\8\4\11.\7\14';
        $number = preg_replace($pattern, $replacement,$string);
        if (is_numeric($number)){
            if (strpos($number, ".") === false )  return intval($number);
            else return floatval($number);
        }
        else return $string;
    }

    public function walk_recursive(&$item, $key)
    {
        //if (!empty($this->metas) and isset($this->metas[$key])) {
            $beString = ['cpf','cnpj','areaCode','number','branchNumber','accountNumber','accountNumberDigit','bankNumber','professionId','inssNumber'];
            //$this->metas[$key] = trim($this->metas[$key]);
            if (in_array($key, $beString)) {
                $item = (string) $item;
            } else {
                $item = $this->getTypedNumber(trim($item));
            }
        //}
    }

    public function proposals(Request $request)
    {
       $inputs = $request->all();
        if (empty($inputs['cpf'])) {
            return false;
        }
        
        $borrower = Borrower::where('cpf',$inputs['cpf'])->first();
        if (empty($borrower)) {
            return false;
        }
        if (!isset($borrower->proposal) or empty($borrower->proposal)) {
            exit('json vazio');
        }

        if (empty($this->clientId) or empty($this->token)) {
            $this->authenticate();
        }
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $proposal = json_decode($borrower->proposal, true);
        
        array_walk_recursive($proposal , [$this , 'walk_recursive']);

        try {
            $response = Http::withHeaders([
                'authorization' => 'Bearer '.$this->token,
                'user-agent' => $user_agent,
                'client-id' => $this->clientId,
            ])->post($this->base_url.'proposals',$proposal);
        } catch (Exception $e) {
            exit('falha na api BPC');
        } 
        var_dump($proposal);
        var_dump((string) $response->getBody());
 
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
