<?php

namespace Database\Seeders;

use App\Models\Breed;
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

        // 2. Tworzenie ras psów w nowej tabeli `breeds`
        $breedNames = [
            'Yorkshire Terrier', 'Labrador Retriever', 'Golden Retriever', 'Beagle',
            'Pudel Miniaturowy', 'Owczarek Niemiecki', 'Buldog Francuski',
            'Shih Tzu', 'Maltańczyk', 'Mieszaniec / Kundelek', 'Bichon Frise',
            'Cavalier King Charles Spaniel', 'Sznaucer Miniaturowy', 'Cocker Spaniel'
        ];

        $breedsMap = [];
        foreach ($breedNames as $bName) {$breed = Breed::create([
                'user_id' => null, // Rasy globalne
                'name' => $bName,
            ]);
            $breedsMap[$bName] =$breed->id;
        }

        // 3. Katalog usług groomera (bez ceny domyślnej)
        $servicesNames = [
            'Strzyżenie psa',
            'Kąpiel i czesanie',
            'Obcinanie pazurków',
            'Trymowanie sierści',
            'Czyszczenie uszu i oczu',
            'Kąpiel higieniczna',
            'Rozczesywanie kołtunów',
        ];

        $services = [];
        foreach ($servicesNames as $sName) {$services[] = Service::create([
                'user_id' => $user->id,
                'name' => $sName,
            ]);
        }

        // Przykładowe ceny przypisane do usług w wizytach
        $servicePrices = [150, 120, 40, 180, 30, 90, 100];

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
        foreach ($clientsNames as$index => $clientName) {$client = Client::create([
                'user_id' => $user->id,
                'name' => $clientName,
                'phone' => '+48 600 ' . rand(100, 999) . ' ' . rand(100, 999),
                'email' => 'klient' . ($index + 1) . '@example.com',
            ]);

            // Dedykowany pies Lucky dla pierwszego klienta
            if ($index === 0) {$dog = Dog::create([
                    'client_id' => $client->id,
                    'breed_id' => $breedsMap['Yorkshire Terrier'],
                    'name' => 'Lucky',
                    'age' => 3.0,
                    'weight' => 3.2,
                ]);

                // Wizyty historyczne dla Lucky'ego
                Visit::create([
                    'user_id' => $user->id,
                    'client_id' => $client->id,
                    'dog_id' => $dog->id,
                    'service_id' => $services[1]->id, // Kąpiel i czesanie
                    'start_time' => '2026-09-10 10:00:00',
                    'end_time' => '2026-09-10 11:30:00',
                    'price' => 120,
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
                    'price' => 40,
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
                    'price' => 150,
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
                    'price' => 150,
                    'description' => 'Standardowe strzyżenie i kąpiel pielęgnacyjna.',
                    'status' => 'scheduled',
                ]);
            } else {
                // Pozostali klienci i ich psy
                $dName = array_shift($dogNames) ?? 'Pies_' .$index;
                $randomBreedId = array_rand($breedsMap);

                $dog = Dog::create([
                    'client_id' => $client->id,
                    'breed_id' => $breedsMap[$randomBreedId],
                    'name' => $dName,
                    'age' => rand(1, 12),
                    'weight' => rand(25, 300) / 10,
                ]);

                // Generowanie 3-5 wizyt na psa
                for ($v = 0; $v < rand(3, 5); $v++) {$serviceIndex = array_rand($services);$service = $services[$serviceIndex];

                    $daysOffset = rand(-45, 30);$hour = rand(8, 16);

                    $startTime = Carbon::now()->addDays($daysOffset)->setHour($hour)->setMinute(0)->setSecond(0);
                    $endTime = (clone$startTime)->addHours(rand(1, 2));

                    $status =$startTime->isPast() ? 'completed' : 'scheduled';

                    Visit::create([
                        'user_id' => $user->id,
                        'client_id' => $client->id,
                        'dog_id' => $dog->id,
                        'service_id' => $service->id,
                        'start_time' => $startTime->toDateTimeString(),
                        'end_time' => $endTime->toDateTimeString(),
                        'price' => $servicePrices[$serviceIndex],
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