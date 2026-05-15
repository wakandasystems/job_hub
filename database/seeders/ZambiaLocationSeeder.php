<?php

namespace Database\Seeders;

use Botble\Base\Enums\BaseStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ZambiaLocationSeeder extends Seeder
{
    public function run(): void
    {
        $country = DB::table('countries')->where('name', 'Zambia')->first();

        if (! $country) {
            $this->call(AfricanCountrySeeder::class);

            $country = DB::table('countries')->where('name', 'Zambia')->first();
        }

        if (! $country) {
            return;
        }

        $now = Carbon::now();

        $provinces = [
            ['Central', 'CE'],
            ['Copperbelt', 'CB'],
            ['Eastern', 'EA'],
            ['Luapula', 'LP'],
            ['Lusaka', 'LS'],
            ['Muchinga', 'MU'],
            ['Northern', 'NO'],
            ['North-Western', 'NW'],
            ['Southern', 'SO'],
            ['Western', 'WE'],
        ];

        $stateIds = [];

        foreach ($provinces as $order => [$name, $abbreviation]) {
            $values = [
                'slug' => Str::slug($name),
                'abbreviation' => $abbreviation,
                'country_id' => $country->id,
                'order' => $order,
                'is_default' => $name === 'Lusaka' ? 1 : 0,
                'status' => BaseStatusEnum::PUBLISHED,
                'updated_at' => $now,
            ];

            $state = DB::table('states')->where('name', $name)->where('country_id', $country->id);

            if ($state->exists()) {
                $state->update($values);
            } else {
                DB::table('states')->insert([
                    'name' => $name,
                    ...$values,
                    'created_at' => $now,
                ]);
            }

            $stateIds[$name] = DB::table('states')
                ->where('name', $name)
                ->where('country_id', $country->id)
                ->value('id');
        }

        $cities = [
            'Central' => ['Kabwe', 'Kapiri Mposhi', 'Mkushi', 'Mumbwa', 'Serenje', 'Chibombo', 'Chisamba', 'Luano', 'Ngabwe', 'Shibuyunji'],
            'Copperbelt' => ['Ndola', 'Kitwe', 'Chingola', 'Mufulira', 'Luanshya', 'Kalulushi', 'Chililabombwe', 'Lufwanyama', 'Masaiti', 'Mpongwe'],
            'Eastern' => ['Chipata', 'Petauke', 'Katete', 'Lundazi', 'Chadiza', 'Mambwe', 'Nyimba', 'Sinda', 'Vubwi', 'Lumezi', 'Chasefu', 'Kasenengwa'],
            'Luapula' => ['Mansa', 'Samfya', 'Nchelenge', 'Kawambwa', 'Mwense', 'Milenge', 'Chiengi', 'Chembe', 'Lunga', 'Chipili'],
            'Lusaka' => ['Lusaka', 'Chongwe', 'Kafue', 'Luangwa', 'Rufunsa', 'Chilanga'],
            'Muchinga' => ['Chinsali', 'Mpika', 'Nakonde', 'Isoka', 'Mafinga', 'Shiwangandu', 'Lavushimanda', 'Kanchibiya'],
            'Northern' => ['Kasama', 'Mbala', 'Mpulungu', 'Mporokoso', 'Luwingu', 'Mungwi', 'Nsama', 'Kaputa', 'Lupososhi', 'Senga Hill'],
            'North-Western' => ['Solwezi', 'Mwinilunga', 'Kasempa', 'Kabompo', 'Zambezi', 'Mufumbwe', 'Chavuma', 'Manyinga', 'Kalumbila', 'Mushindamo'],
            'Southern' => ['Livingstone', 'Choma', 'Mazabuka', 'Monze', 'Kalomo', 'Sinazongwe', 'Kazungula', 'Namwala', 'Gwembe', 'Siavonga', 'Pemba', 'Zimba'],
            'Western' => ['Mongu', 'Senanga', 'Sesheke', 'Kaoma', 'Kalabo', 'Lukulu', 'Limulunga', 'Nalolo', 'Mwandi', 'Shangombo', 'Sikongo', 'Sioma', 'Nkeyema'],
        ];

        $order = 0;

        foreach ($cities as $province => $cityNames) {
            foreach ($cityNames as $cityName) {
                $values = [
                    'slug' => Str::slug($cityName),
                    'state_id' => $stateIds[$province],
                    'country_id' => $country->id,
                    'order' => $order++,
                    'is_default' => $cityName === 'Lusaka' ? 1 : 0,
                    'status' => BaseStatusEnum::PUBLISHED,
                    'updated_at' => $now,
                ];

                $city = DB::table('cities')->where('name', $cityName)->where('country_id', $country->id);

                if ($city->exists()) {
                    $city->update($values);
                } else {
                    DB::table('cities')->insert([
                        'name' => $cityName,
                        ...$values,
                        'created_at' => $now,
                    ]);
                }
            }
        }
    }
}
