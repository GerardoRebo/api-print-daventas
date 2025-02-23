<?php

namespace App\Models;

use App\Exceptions\OperationalException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Devolucione extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $with = ['ventaticket', 'turno', 'user'];

    //uno a uno
    public function ventaticket()
    {
        return $this->belongsTo('App\Models\Ventaticket');
    }
    //relacion uno a muchos inversa

    public function devoluciones_articulos(){
        return $this->hasMany('App\Models\DevolucionesArticulo');
    }
    public function turno()
    {
        return $this->belongsTo('App\Models\Turno');
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
    //relacion uno a muchos polimorfica
    public function histories(){
        
        return $this->morphMany('App\Models\History', 'historiable');
    }
    function registerArticulo(VentaticketArticulo $articulo, $cantidad) {
        $dineroDevuelto= ($articulo->precio_final/$articulo->cantidad)*$cantidad;
        if ($cantidad > $articulo->cantidad) {
            throw new OperationalException("La cantidad que quieres devolver es mayor a la vendida", 1);
        }
        DevolucionesArticulo::updateOrCreate(
            [
                "devolucione_id" => $this->id,
                "ventaticket_articulo_id" => $articulo->id,
                "product_id" => $articulo->product_id
            ],
            ["cantidad_devuelta" => $cantidad, "dinero_devuelto" =>  $dineroDevuelto]
        );
        
    }
}
