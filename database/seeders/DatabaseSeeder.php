<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Dog;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Jeden główny użytkownik (groomer)
        $user = User::create([
            'name' => 'Jan Kowalski',
            'email' => 'jan@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 2. Katalog usług groomera
        $servicesData = [
            ['name' => 'Strzyżenie psa', 'default_price' => 150.00],
            ['name' => 'Kąpiel i czesanie', 'default_price' => 120.00],
            ['name' => 'Obcinanie pazurków', 'default_price' => 40.00],
            ['name' => 'Trymowanie sierści', 'default_price' => 180.00],
            ['name' => 'Czyszczenie uszu i oczu', 'default_price' => 30.00],
            ['name' => 'Kąpiel higieniczna', 'default_price' => 90.00],
            ['name' => 'Rozczesywanie kołtunów', 'default_price' => 100.00],
        ];

        $services = [];
        foreach ($servicesData as $sData) {
            $services[] = Service::create([
                'user_id' => $user->id,
                'name' => $sData['name'],
                'default_price' => $sData['default_price'],
            ]);
        }

        // 3. Rasa i imiona dla psa "Lucky" z zrzutu ekranu
        $breeds = [
            'Yorkshire Terrier', 'Labrador Retriever', 'Golden Retriever', 'Beagle',
            'Pudel Miniaturowy', 'Owczarek Niemiecki', 'Buldog Francuski',
            'Shih Tzu', 'Maltańczyk', 'Mieszaniec'
        ];

        // Lista klientów
        $clientsNames = [
            'Jan Kowalski', 'Mateusz Wiśniewski', 'Ewa Dąbrowska', 'Tomasz Kamiński',
            'Magdalena Wójcik', 'Krzysztof Kowalczyk', 'Anna Lewandowska', 'Piotr Zieliński',
            'Katarzyna Szymańska', 'Michał Woźniak', 'Agnieszka Kozłowska', 'Bartek Jankowski',
            'Karolina Mazur', 'Paweł Wojciechowski', 'Monika Krawczyk', 'Marcin Kaczmarek',
            'Joanna Piotrowska', 'Rafał Grabowski', 'Sylwia Zając', 'Łukasz Pawłowski'
        ];

        $dogNames = ['Lucky', 'Burek', 'Max', 'Luna', 'Bella', 'Charlie', 'Molly', 'Rocky', 'Buddy', 'Daisy', 'Milo', 'Coco', 'Oscar', 'Teddy', 'Toby'];

        // 4. Tworzenie 20 klientów, ich psów oraz wizyt
        foreach ($clientsNames as $index => $clientName) {
            $client = Client::create([
                'user_id' => $user->id,
                'name' => $clientName,
                'phone' => '+48 600 ' . rand(100, 999) . ' ' . rand(100, 999),
                'email' => 'klient' . ($index + 1) . '@example.com',
            ]);

            // Dedykowany pies Lucky dla pierwszego klienta (zgodnie ze screenem)
            if ($index === 0) {
                $dog = Dog::create([
                    'client_id' => $client->id,
                    'name' => 'Lucky',
                    'breed' => 'Yorkshire Terrier',
                    'age' => 3,
                    'weight' => 3.20,
                ]);

                // Wizyty historyczne z ekranu
                Visit::create([
                    'user_id' => $user->id,
                    'client_id' => $client->id,
                    'dog_id' => $dog->id,
                    'service_id' => $services[1]->id, // Kąpiel i czesanie
                    'start_time' => '2026-09-10 10:00:00',
                    'end_time' => '2026-09-10 11:30:00',
                    'price' => 120.00,
                    'description' => 'Przegląd skóry, wyczesywanie podszerstka. Pies zachowywał się bardzo dobrze.',
                    'status' => 'completed',
                ]);

                Visit::create([
                    'user_id' => $user->id,
                    'client_id' => $client->id,
                    'dog_id' => $dog->id,
                    'service_id' => $services[2]->id, // Obcinanie pazurków
                    'start_time' => '2026-08-22 14:00:00',
                    'end_time' => '2026-08-22 14:30:00',
                    'price' => 40.00,
                    'description' => 'Krótka wizyta, obcięte pazurki na wszystkich łapach.',
                    'status' => 'completed',
                ]);

                Visit::create([
                    'user_id' => $user->id,
                    'client_id' => $client->id,
                    'dog_id' => $dog->id,
                    'service_id' => $services[0]->id, // Strzyżenie psa
                    'start_time' => '2026-08-05 09:00:00',
                    'end_time' => '2026-08-05 10:30:00',
                    'price' => 150.00,
                    'description' => 'Pełne strzyżenie letnie, skracanie firanki i uszu.',
                    'status' => 'completed',
                ]);

                // Przyszła wizyta dla Lucky'ego
                Visit::create([
                    'user_id' => $user->id,
                    'client_id' => $client->id,
                    'dog_id' => $dog->id,
                    'service_id' => $services[0]->id,
                    'start_time' => '2026-10-13 11:00:00',
                    'end_time' => '2026-10-13 13:00:00',
                    'price' => 150.00,
                    'description' => 'Standardowe strzyżenie i kąpiel pielęgnacyjna.',
                    'status' => 'scheduled',
                ]);
            } else {
                // Pozostali klienci i ich psy
                $dName = array_shift($dogNames) ?? 'Pies_' . $index;
                $dog = Dog::create([
                    'client_id' => $client->id,
                    'name' => $dName,
                    'breed' => $breeds[array_rand($breeds)],
                    'age' => rand(1, 12),
                    'weight' => rand(25, 300) / 10,
                ]);

                // Generowanie 3-5 wizyt na psa
                for ($v = 0; $v < rand(3, 5); $v++) {
                    $service = $services[array_rand($services)];

                    // Rozrzut od -45 dni do +30 dni od dziś
                    $daysOffset = rand(-45, 30);
                    $hour = rand(8, 16);

                    $startTime = Carbon::now()->addDays($daysOffset)->setHour($hour)->setMinute(0)->setSecond(0);
                    $endTime = (clone $startTime)->addHours(rand(1, 2));

                    $status = $startTime->isPast() ? 'completed' : 'scheduled';

                    Visit::create([
                        'user_id' => $user->id,
                        'client_id' => $client->id,
                        'dog_id' => $dog->id,
                        'service_id' => $service->id,
                        'start_time' => $startTime->toDateTimeString(),
                        'end_time' => $endTime->toDateTimeString(),
                        'price' => $service->default_price,
                        'description' => 'Usługa: ' . $service->name . '. Pies zachowywał się bez zastrzeżeń.',
                        'status' => $status,
                    ]);
                }
            }
        }

        // 5. Wyczyszczenie pamięci podręcznej w Redisie
        Cache::flush();
    }
}
