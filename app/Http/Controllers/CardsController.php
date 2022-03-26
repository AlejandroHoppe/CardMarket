<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Card;
use App\Models\Collection;
use App\Models\CardCollection;
use App\Models\LotOnSale;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CardsController extends Controller
{
    public function registerCard(Request $req)
    {
        $respuesta = ['status' => 1, 'msg' => ''];
        //Validar datos
        $validator = Validator::make(json_decode($req->getContent(), true), [
            'name' => ['required', 'max:50'],
            'description' => ['required', 'max:500'],
            'collection' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            $respuesta['status'] = 0;
            $respuesta['msg'] = $validator->errors();
        } else {
            //Generamos la nueva carta

            $data = $req->getContent();
            $data = json_decode($data);

            $collection = Collection::where('id','=',$data->collection)->first();

            if ($collection) {
                $card = new Card();
                $card->name = $data->name;
                $card->description = $data->description;

                try {
                    $card->save();
                    $cardCollection = new CardCollection();
                    $cardCollection->card_id = $card->id;
                    $cardCollection->collection_id = $collection->id;
                    $cardCollection->save();
                    $respuesta['msg'] =
                        'Carta guardada con id ' .
                        $card->id .
                        ' y colleccion guardada con el id ' .
                        $cardCollection->id;
                } catch (\Exception $e) {
                    $respuesta['status'] = 0;
                    $respuesta['msg'] =
                        'Se ha producido un error: ' . $e->getMessage();
                }
            } else {
                $respuesta['status'] = 0;
                $respuesta['msg'] = 'La coleccion no existe';
            }
        }
        return response()->json($respuesta);
    }

    public function registerCollection(Request $req)
    {
        $respuesta = ['status' => 1, 'msg' => ''];
        //Validamos datos
        $validator = Validator::make(json_decode($req->getContent(), true), [
            'name' => ['required', 'max:50'],
            'symbol' => ['required', 'max:100'],
            'launch_date' => ['required', 'date'],
            'cards' => ['required']
        ]);

        if ($validator->fails()) {
            $respuesta['status'] = 0;
            $respuesta['msg'] = $validator->errors();
        } else {
            //Generamos la nueva carta

            $data = $req->getContent();
            $data = json_decode($data);
            $validId =[];
            foreach ($data->cards as $addCard) {
                if(isset($addCard->id)){
                $card = Card::where('id','=',$addCard->id)->first();
                if($card){
                    
                    array_push($validId,$card->id);                
                }
                }elseif (
                            isset($addCard->name) &&
                            isset($addCard->description) 
                        ) {
                            
                            $newCard = new Card();
                            $newCard->name = $addCard->name;
                            $newCard->description = $addCard->description;

                            try {
                                $newCard->save();
                                array_push($validId,$newCard->id);
                                $respuesta['msg'] ='Carta guardada con id ' .$newcard->id;
                                    
                            } catch (\Exception $e) {
                                $respuesta['status'] = 0;
                                $respuesta['msg'] ='Se ha producido un error: ' . $e->getMessage();
                            }
            }else{
                $respuesta['status'] = 0;
                $respuesta['msg'] ='Los datos ingresados no corresponden a los parametros de carta';
            }
            
        }
        if(!empty($validId)){
            $cardsIds = implode (", ",$validId); 
            try{
            $collection = new Collection();
            $collection->name = $data->name;
            $collection->symbol = $data->symbol;
            $collection->launch_date = $data->launch_date;
            $collection->save();

            foreach($validId as $id){
                $cardCollection = new CardCollection();
                $cardCollection->card_id = $id;
                $cardCollection->collection_id = $collection->id;
                $cardCollection->save();
            }
            $respuesta['msg'] ='Se ha creado la colleccion con id: '.$cardCollection->id .' y se le han agregado las cartas: '.$cardsIds;
            
        }catch (\Exception $e) {
            $respuesta['status'] = 0;
            $respuesta['msg'] ='Se ha producido un error: ' . $e->getMessage();
        }
        }

        
    }
    return response()->json($respuesta);
}

public function cardSearcher(Request $req)
    {
        $respuesta = ['status' => 1, 'msg' => ''];

        $validator = Validator::make(json_decode($req->getContent(), true), [
            'search' => ['required', 'max:50']
        ]);

        if ($validator->fails()) {
            $respuesta['status'] = 0;
            $respuesta['msg'] = $validator->errors();
        } else {

        try{
            $data = $req->getContent();
            $data = json_decode($data);

            $search = DB::table('cards')
                        ->select(['id','name','description'])
                        ->where('name','like','%'. $data -> search.'%')
                        ->get();
            $respuesta['msg'] = $search;
        }catch(\Exception $e){
            $respuesta['status'] = 0;
            $respuesta['msg'] ='Se ha producido un error: ' . $e->getMessage();
        }
    }
    return response()->json($respuesta);
}


public function putOnSale(Request $req){
        $respuesta = ['status' => 1, 'msg' => ''];
        
        $validator = Validator::make(json_decode($req->getContent(), true),
         [
            'card_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer'],
            'total_price' => ['required', 'numeric','min:0','not_in:0'],

        ]);

        if ($validator->fails()) {
            $respuesta['status'] = 0;
            $respuesta['msg'] = $validator->errors();
        } else {
            $data = $req->getContent();
            $data = json_decode($data);

            $card = Card::where('id','=',$data->card_id)->first();

            if($card){
                $venta = new Ventas();
                $venta->card_id = $data->card_id;
                $venta->quantity = $data->quantity;
                $venta->total_price = $data->total_price;
                $venta->seller_id = $req->user->id ;
                try {
                    $venta->save();
                    $respuesta['msg'] =
                        'Venta guardada con id ' .
                        $venta->id;
                } catch (\Exception $e) {
                    $respuesta['status'] = 0;
                    $respuesta['msg'] =
                        'Se ha producido un error: ' . $e->getMessage();
                }

            }else{
                $respuesta['msg'] =
                        'La carta no existe';
                
            }
    }
    return response()->json($respuesta);
}

public function cardsOnSale(Request $req)
    {
        $respuesta = ['status' => 1, 'msg' => ''];

        $validator = Validator::make(json_decode($req->getContent(), true), [
            'search' => ['required', 'max:50']
        ]);

        if ($validator->fails()) {
            $respuesta['status'] = 0;
            $respuesta['msg'] = $validator->errors();
        } else {

        try{
            $data = $req->getContent();
            $data = json_decode($data);

            $search = DB::table('ventas')
                        ->join('users', 'users.id', '=', 'ventas.seller_id')
                        ->join('cards', 'cards.id', '=', 'ventas.card_id')
                        ->select('cards.name', 'ventas.quantity', 'ventas.total_price', 'users.username as seller'  )
                        ->where('cards.name','like','%'. $data -> search.'%')
                        ->orderBy('ventas.total_price','ASC')
                        ->get();
            $respuesta['msg'] = $search;
        }catch(\Exception $e){
            $respuesta['status'] = 0;
            $respuesta['msg'] ='Se ha producido un error: ' . $e->getMessage();
        }
    }
    return response()->json($respuesta);
}



}
