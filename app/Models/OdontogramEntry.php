<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class OdontogramEntry extends Model
{
    use Auditable, BelongsToClinic, HasFactory;

    public const SURFACES = ['M', 'O', 'D', 'B', 'L'];

    public const CONDITIONS = [
        'healthy', 'caries', 'filling', 'root_canal', 'crown',
        'missing', 'implant', 'to_extract', 'bridge', 'veneer', 'sealant',
    ];

    /** FDI permanent + primary tooth numbers. */
    public static function validTeeth(): array
    {
        static $teeth;
        if ($teeth === null) {
            $perm = [11,12,13,14,15,16,17,18,21,22,23,24,25,26,27,28,
                     31,32,33,34,35,36,37,38,41,42,43,44,45,46,47,48];
            $prim = [51,52,53,54,55,61,62,63,64,65,71,72,73,74,75,81,82,83,84,85];
            $teeth = array_merge($perm, $prim);
        }

        return $teeth;
    }

    protected $fillable = [
        'clinic_id', 'patient_id', 'tooth_number', 'surface',
        'condition', 'notes', 'recorded_by',
    ];

    /** Human-readable current chart: [tooth => ['whole'=>cond, 'surfaces'=>[s=>cond]]] */
    public static function chartFor(Patient $patient): array
    {
        $chart = [];
        foreach (static::where('patient_id', $patient->id)->orderBy('tooth_number')->get() as $e) {
            $chart[$e->tooth_number][$e->surface ?? 'whole'] = $e->condition;
        }

        return $chart;
    }
}
