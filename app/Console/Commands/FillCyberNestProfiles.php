<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill realistic Bangladesh-format profile + KYC data onto already-created
 * CyberNest clients. Names are generated UNIQUE across the whole batch
 * (honorific + given + optional middle + surname) so no two clients share a name.
 * Idempotent: re-running (in the same id order) regenerates the same data.
 */
class FillCyberNestProfiles extends Command
{
    protected $signature = 'cybernest:fill-profiles
        {--reseller=46 : Reseller user id whose clients to fill}
        {--limit=0 : only first N (0 = all)}
        {--dry-run : print a few samples without writing}';

    protected $description = 'Fill realistic, UNIQUE Bangladesh-format names + KYC profiles on CyberNest clients';

    // ---- name pools (honorific + given [+ middle] + surname) ----
    private array $maleHon = ['Md.', 'Mohammad', 'Md.', '', 'Md.', ''];
    private array $maleGiven = ['Abdur','Rakibul','Tanvir','Mizanur','Nazmul','Ariful','Mahmudul','Rasel','Imran','Saiful','Mamunur','Jubayer','Naimur','Anisur','Jahangir','Habibur','Asadul','Faruk','Shahin','Delwar','Monir','Sohag','Nayeem','Sajjad','Robiul','Shahadat','Selim','Babul','Aminul','Shariful','Rubel','Tuhin','Jakir','Polash','Liton','Sumon','Rezaul','Belal','Helal','Kamal','Jamal','Nurul','Mizan','Anwar','Forhad','Masud','Kamrul','Ashraful','Rifat','Sabbir','Tariqul','Mehedi','Shamim','Jewel','Russel'];
    private array $maleMiddle = ['Hasan','Hossain','Ahmed','Karim','Kabir','Mahmud','Alam','Anwar','Aziz','Rashid','Mostafa','Kamal','Habib','Latif','Mannan','Wahid','Razzak','Sarwar','Nur','Iqbal'];
    private array $femaleHon = ['Mst.', '', '', 'Mosammat', '', ''];
    private array $femaleGiven = ['Fatema','Ayesha','Sumaiya','Nusrat','Tahmina','Sharmin','Rabeya','Nasrin','Marufa','Jannatul','Sadia','Israt','Tania','Lamia','Rumana','Sonia','Shahnaz','Kohinoor','Mim','Nadia','Sabina','Rokeya','Shilpi','Mukta','Tasnim','Farzana','Jesmin','Rehana','Salma','Rina','Shapla','Morjina','Habiba','Sumona','Anjuman','Parveen','Dilruba','Shahida','Asma','Razia','Tisha','Maliha','Sanjida','Rupa','Mitu'];
    private array $femaleMiddle = ['Jahan','Akter','Sultana','Khatun','Ferdous','Nahar','Yasmin','Parvin','Banu','Begum'];
    private array $femaleSur = ['Akter','Begum','Sultana','Khatun','Parvin','Akhter','Nahar','Yasmin'];
    private array $surnames = ['Islam','Hossain','Ahmed','Rahman','Khan','Chowdhury','Sarkar','Uddin','Mia','Hasan','Ali','Karim','Haque','Bhuiyan','Sheikh','Molla','Parvez','Alam','Talukder','Mahmud','Siddique','Kabir','Sarder','Howlader','Pramanik','Munshi','Gazi','Patwary','Akand','Biswas','Mridha','Sikder','Bepari','Hawlader'];

    // ---- address / contact pools ----
    private array $areas = ['Dhanmondi','Gulshan','Banani','Mirpur','Uttara','Mohammadpur','Motijheel','Badda','Tejgaon','Jatrabari','Mohakhali','Khilgaon','Bashundhara R/A','Shantinagar','Malibagh','Rampura','Shyamoli','Kafrul'];
    private array $operators = ['013','014','015','016','017','018','019'];
    private array $emailDomains = ['gmail.com','yahoo.com','hotmail.com','outlook.com'];
    private array $places = [
        ['Dhaka','Dhaka','1205'],['Dhaka','Dhaka','1212'],['Mirpur','Dhaka','1216'],['Savar','Dhaka','1340'],
        ['Gazipur','Dhaka','1700'],['Narayanganj','Dhaka','1400'],['Tangail','Dhaka','1900'],['Narsingdi','Dhaka','1600'],
        ['Chattogram','Chattogram','4000'],['Cumilla','Chattogram','3500'],["Cox's Bazar",'Chattogram','4700'],['Feni','Chattogram','3900'],['Noakhali','Chattogram','3800'],
        ['Khulna','Khulna','9000'],['Jashore','Khulna','7400'],['Kushtia','Khulna','7000'],
        ['Rajshahi','Rajshahi','6000'],['Bogura','Rajshahi','5800'],['Pabna','Rajshahi','6600'],
        ['Sylhet','Sylhet','3100'],['Moulvibazar','Sylhet','3200'],
        ['Barishal','Barishal','8200'],['Rangpur','Rangpur','5400'],['Dinajpur','Rangpur','5200'],['Mymensingh','Mymensingh','2200'],
    ];
    private array $companyTypes = ['Telecom','Communications','Enterprise','Networks','IT Solutions','Traders','Technologies'];

    /** names already handed out this run (lower-cased) -> guarantees uniqueness */
    private array $used = [];

    public function handle(): int
    {
        $resellerId = (int) $this->option('reseller');
        $limit = (int) $this->option('limit');
        $dry = (bool) $this->option('dry-run');

        $reviewedBy = User::where('role', 'super_admin')->value('id') ?? $resellerId;

        $q = User::where('parent_id', $resellerId)->where('role', 'client')->orderBy('id');
        if ($limit > 0) {
            $q->limit($limit);
        }
        $clients = $q->get(['id', 'username', 'created_at']);

        $this->info(sprintf('%d clients under reseller #%d | reviewed_by=%d | %s',
            $clients->count(), $resellerId, $reviewedBy, $dry ? 'DRY-RUN' : 'LIVE'));

        $done = 0;
        $bar = $dry ? null : $this->output->createProgressBar($clients->count());
        $bar?->start();

        foreach ($clients->chunk(200) as $chunk) {
            DB::transaction(function () use ($chunk, $reviewedBy, $dry, &$done, $bar) {
                foreach ($chunk as $c) {
                    $num = $c->username;
                    mt_srand((int) substr($num, -7)); // stable phone/address/NID per account

                    $isMale = mt_rand(0, 1) === 1;
                    $name = $this->uniqueName($isMale);

                    [$city, $division, $postal] = $this->places[array_rand($this->places)];
                    $area = $this->pick($this->areas);
                    $addr = 'House ' . mt_rand(1, 99) . ', Road ' . mt_rand(1, 30) . ', ' . $area;

                    $phone = $this->pick($this->operators) . str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
                    $altPhone = $this->pick($this->operators) . str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);

                    $tail = substr($num, -6); // unique across both ranges
                    $slug = $this->emailSlug($name);
                    $email = "{$slug}{$tail}@" . $this->pick($this->emailDomains);

                    $isCompany = mt_rand(1, 100) <= 15;
                    $companyName = $companyEmail = $companyWeb = null;
                    $accountType = 'individual';
                    if ($isCompany) {
                        $accountType = 'company';
                        $companyName = $this->pick($this->surnames) . ' ' . $this->pick($this->companyTypes);
                        $cslug = strtolower(preg_replace('/[^a-z0-9]/i', '', $companyName));
                        $companyEmail = "info{$tail}@{$cslug}.com.bd";
                        $companyWeb = "https://www.{$cslug}.com.bd";
                    }

                    if (mt_rand(1, 100) <= 60) {
                        $nid = (string) mt_rand(1000000000, 1999999999);
                    } else {
                        $nid = mt_rand(1972, 2003) . str_pad((string) mt_rand(0, 9999999999999), 13, '0', STR_PAD_LEFT);
                    }

                    if ($dry) {
                        $this->line(sprintf('%s | %-26s | %s | %s, %s %s | %s | NID %s | %s',
                            $num, $name, $phone, $city, $division, $postal, $email, $nid, $accountType));
                        $done++;
                        continue;
                    }

                    DB::table('users')->where('id', $c->id)->update([
                        'name' => $name,
                        'email' => $email,
                        'contact_email' => $email,
                        'phone' => $phone,
                        'alt_phone' => $altPhone,
                        'address' => $addr,
                        'city' => $city,
                        'state' => $division,
                        'country' => 'Bangladesh',
                        'zip_code' => $postal,
                        'company_name' => $companyName,
                        'company_email' => $companyEmail,
                        'company_website' => $companyWeb,
                        'currency' => 'BDT',
                        'email_verified_at' => $c->created_at,
                        'updated_at' => now(),
                    ]);

                    DB::table('kyc_profiles')->updateOrInsert(
                        ['user_id' => $c->id],
                        [
                            'account_type' => $accountType,
                            'full_name' => $name,
                            'contact_person' => $isCompany ? $name : null,
                            'phone' => $phone,
                            'alt_phone' => $altPhone,
                            'address_line1' => $addr,
                            'address_line2' => "{$city}, {$division}",
                            'city' => $city,
                            'state' => $division,
                            'postal_code' => $postal,
                            'country' => 'BD',
                            'id_type' => 'national_id',
                            'id_number' => $nid,
                            'id_expiry_date' => null,
                            'submitted_at' => $c->created_at,
                            'reviewed_at' => now(),
                            'reviewed_by' => $reviewedBy,
                            'updated_at' => now(),
                            'created_at' => $c->created_at,
                        ]
                    );

                    $done++;
                    $bar?->advance();
                }
            });
        }

        $bar?->finish();
        $this->newLine();
        $this->info("Done. profiles filled={$done} | unique names used=" . count($this->used));
        return self::SUCCESS;
    }

    /** Build a realistic BD name unique across this run; add a middle name on collision. */
    private function uniqueName(bool $isMale): string
    {
        for ($attempt = 0; $attempt < 80; $attempt++) {
            if ($isMale) {
                $hon = $this->pick($this->maleHon);
                $given = $this->pick($this->maleGiven);
                $sur = $this->pick($this->surnames);
                $mid = ($attempt > 0 || mt_rand(0, 1)) ? ' ' . $this->pickDistinct($this->maleMiddle, [$given, $sur]) : '';
            } else {
                $hon = $this->pick($this->femaleHon);
                $given = $this->pick($this->femaleGiven);
                $sur = $this->pick($this->femaleSur);
                $mid = ($attempt > 0 || mt_rand(0, 1)) ? ' ' . $this->pickDistinct($this->femaleMiddle, [$given, $sur]) : '';
            }
            $name = trim(($hon ? $hon . ' ' : '') . $given . $mid . ' ' . $sur);
            $key = strtolower($name);
            if (! isset($this->used[$key])) {
                $this->used[$key] = true;
                return $name;
            }
        }
        // extremely unlikely fallback: append a second surname to force uniqueness
        $base = $isMale ? $this->pick($this->maleGiven) : $this->pick($this->femaleGiven);
        do {
            $name = $base . ' ' . $this->pick($this->surnames) . ' ' . $this->pick($this->surnames);
            $key = strtolower($name);
        } while (isset($this->used[$key]));
        $this->used[$key] = true;
        return $name;
    }

    /** email local-part: drop honorifics, use given + surname. */
    private function emailSlug(string $name): string
    {
        $hon = ['md', 'mst', 'mohammad', 'mosammat'];
        $words = array_values(array_filter(explode(' ', $name), fn ($w) => ! in_array(strtolower(rtrim($w, '.')), $hon, true)));
        $first = $words[0] ?? 'user';
        $last = end($words) ?: 'bd';
        return strtolower(preg_replace('/[^a-z]/i', '', $first . $last));
    }

    private function pick(array $a): string
    {
        return $a[array_rand($a)];
    }

    /** pick from $a but not equal to any value in $avoid (avoids "Akter Akter"). */
    private function pickDistinct(array $a, array $avoid): string
    {
        for ($i = 0; $i < 12; $i++) {
            $v = $a[array_rand($a)];
            if (! in_array($v, $avoid, true)) {
                return $v;
            }
        }
        return $a[array_rand($a)];
    }
}
