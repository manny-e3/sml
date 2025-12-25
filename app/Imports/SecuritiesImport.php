<?php

namespace App\Imports;

use App\Models\Security;
use Maatwebsite\Excel\Concerns\ToModel;

class SecuritiesImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Security([
            //
        ]);
    }
}
