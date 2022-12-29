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

    public function borrower(Request $request)
    {
        $defaults = array(
            'name'=>'',
            'cpf'=>'',
        );
        $inputs = array_merge($defaults, array_intersect_key($request->all(),$defaults));
        if (empty($inputs['name']) or empty($inputs['cpf'])) {
            return false;
        }
        $borrower = Borrower::_firstOrCreate(
            ['cpf' => $inputs['cpf']],
            ['name' =>  $inputs['name']],
        );
        return true;
    }

    public function borrowerMeta(Request $request)
    {
        $defaults = array(
            'cpf'=>'',
            'field'=>'',
            'value'=>'',
        );
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
        if (!empty($this->metas) and isset($this->metas[$key])) {
            $beString = ['cpf','areaCode','number'];
            $this->metas[$key] = trim($this->metas[$key]);
            if (in_array($key, $beString)) {
                $item = (string) $this->metas[$key];
            } else {
                $item = $this->getTypedNumber(trim($this->metas[$key]));
            }
        }
    }

    public function proposals(Request $request)
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
    }

}
