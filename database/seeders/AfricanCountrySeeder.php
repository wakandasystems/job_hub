<?php

namespace Database\Seeders;

use Botble\Base\Enums\BaseStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AfricanCountrySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $countries = [
            ['Zambia', 'Zambian', 'ZM'],
            ['Algeria', 'Algerian', 'DZ'],
            ['Angola', 'Angolan', 'AO'],
            ['Benin', 'Beninese', 'BJ'],
            ['Botswana', 'Motswana', 'BW'],
            ['Burkina Faso', 'Burkinabe', 'BF'],
            ['Burundi', 'Burundian', 'BI'],
            ['Cabo Verde', 'Cabo Verdean', 'CV'],
            ['Cameroon', 'Cameroonian', 'CM'],
            ['Central African Republic', 'Central African', 'CF'],
            ['Chad', 'Chadian', 'TD'],
            ['Comoros', 'Comoran', 'KM'],
            ['Republic of the Congo', 'Congolese', 'CG'],
            ['Democratic Republic of the Congo', 'Congolese', 'CD'],
            ["Cote d'Ivoire", 'Ivorian', 'CI'],
            ['Djibouti', 'Djiboutian', 'DJ'],
            ['Egypt', 'Egyptian', 'EG'],
            ['Equatorial Guinea', 'Equatoguinean', 'GQ'],
            ['Eritrea', 'Eritrean', 'ER'],
            ['Eswatini', 'Swazi', 'SZ'],
            ['Ethiopia', 'Ethiopian', 'ET'],
            ['Gabon', 'Gabonese', 'GA'],
            ['Gambia', 'Gambian', 'GM'],
            ['Ghana', 'Ghanaian', 'GH'],
            ['Guinea', 'Guinean', 'GN'],
            ['Guinea-Bissau', 'Bissau-Guinean', 'GW'],
            ['Kenya', 'Kenyan', 'KE'],
            ['Lesotho', 'Basotho', 'LS'],
            ['Liberia', 'Liberian', 'LR'],
            ['Libya', 'Libyan', 'LY'],
            ['Madagascar', 'Malagasy', 'MG'],
            ['Malawi', 'Malawian', 'MW'],
            ['Mali', 'Malian', 'ML'],
            ['Mauritania', 'Mauritanian', 'MR'],
            ['Mauritius', 'Mauritian', 'MU'],
            ['Morocco', 'Moroccan', 'MA'],
            ['Mozambique', 'Mozambican', 'MZ'],
            ['Namibia', 'Namibian', 'NA'],
            ['Niger', 'Nigerien', 'NE'],
            ['Nigeria', 'Nigerian', 'NG'],
            ['Rwanda', 'Rwandan', 'RW'],
            ['Sao Tome and Principe', 'Sao Tomean', 'ST'],
            ['Senegal', 'Senegalese', 'SN'],
            ['Seychelles', 'Seychellois', 'SC'],
            ['Sierra Leone', 'Sierra Leonean', 'SL'],
            ['Somalia', 'Somali', 'SO'],
            ['South Africa', 'South African', 'ZA'],
            ['South Sudan', 'South Sudanese', 'SS'],
            ['Sudan', 'Sudanese', 'SD'],
            ['Tanzania', 'Tanzanian', 'TZ'],
            ['Togo', 'Togolese', 'TG'],
            ['Tunisia', 'Tunisian', 'TN'],
            ['Uganda', 'Ugandan', 'UG'],
            ['Zimbabwe', 'Zimbabwean', 'ZW'],
        ];

        DB::table('countries')->where('name', '!=', 'Zambia')->update(['is_default' => 0]);

        foreach ($countries as $order => [$name, $nationality, $code]) {
            $country = DB::table('countries')->where('name', $name);
            $values = [
                'nationality' => $nationality,
                'code' => $code,
                'order' => $order,
                'is_default' => $name === 'Zambia' ? 1 : 0,
                'status' => BaseStatusEnum::PUBLISHED,
                'updated_at' => $now,
            ];

            if ($country->exists()) {
                $country->update($values);
            } else {
                DB::table('countries')->insert([
                    'name' => $name,
                    'nationality' => $nationality,
                    'code' => $code,
                    'order' => $order,
                    'is_default' => $name === 'Zambia' ? 1 : 0,
                    'status' => BaseStatusEnum::PUBLISHED,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);
            }
        }
    }
}
