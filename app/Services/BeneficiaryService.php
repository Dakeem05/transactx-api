<?php

namespace App\Services;

use App\Models\Beneficiary;

class BeneficiaryService
{
    public function addBeneficiary(string $userId, string $service, array $payload): Beneficiary
    {
        $username = $payload['username'] ?? null;
        $email = $payload['email'] ?? null;

        if (is_null($username) ||  is_null($email)) {
            $existing = Beneficiary::where('user_id', $userId)
                ->where('service', $service)
                ->whereJsonContains('payload->account_number', $payload['account_number'])
                ->whereJsonContains('payload->bank_code', $payload['bank_code'])
                ->first();
    
            if ($existing) {
                $existing->update([
                    'payload' => array_merge($existing->payload, $payload),
                ]);
                return $existing;
            }

        } else {
            $existing = Beneficiary::where('user_id', $userId)
                ->where('service', $service)
                ->whereJsonContains('payload->username', $username)
                ->whereJsonContains('payload->email', $email)
                ->first();
    
            if ($existing) {
                $existing->update([
                    'payload' => array_merge($existing->payload, $payload),
                ]);
                return $existing;
            }

        }

        return Beneficiary::create([
            'user_id' => $userId,
            'service' => $service,
            'payload' => $payload
        ]);
    }

    public function addAirtimeAndDataBeneficiary(string $userId, string $service, array $payload): Beneficiary
    {
        $phone_number = $payload['phone_number'] ?? null;

        if (is_null($phone_number)) {
            $existing = Beneficiary::where('user_id', $userId)
                ->where('service', $service)
                ->whereJsonContains('payload->phone_number', $payload['phone_number'])
                ->first();
    
            if ($existing) {
                $existing->update([
                    'payload' => array_merge($existing->payload, $payload),
                ]);
                return $existing;
            }

        } else {
            $existing = Beneficiary::where('user_id', $userId)
                ->where('service', $service)
                ->whereJsonContains('payload->phone_number', $phone_number)
                ->first();
    
            if ($existing) {
                $existing->update([
                    'payload' => array_merge($existing->payload, $payload),
                ]);
                return $existing;
            }

        }

        return Beneficiary::create([
            'user_id' => $userId,
            'service' => $service,
            'payload' => $payload
        ]);
    }

    public function addCableTVAndUtiltyBeneficiary(string $userId, string $service, array $payload): Beneficiary
    {
        $number = $payload['number'] ?? null;

        if (is_null($number)) {
            $existing = Beneficiary::where('user_id', $userId)
                ->where('service', $service)
                ->whereJsonContains('payload->number', $payload['number'])
                ->first();
    
            if ($existing) {
                $existing->update([
                    'payload' => array_merge($existing->payload, $payload),
                ]);
                return $existing;
            }

        } else {
            $existing = Beneficiary::where('user_id', $userId)
                ->where('service', $service)
                ->whereJsonContains('payload->number', $number)
                ->first();
    
            if ($existing) {
                $existing->update([
                    'payload' => array_merge($existing->payload, $payload),
                ]);
                return $existing;
            }

        }

        return Beneficiary::create([
            'user_id' => $userId,
            'service' => $service,
            'payload' => $payload
        ]);
    }

    public function getBeneficiary(string $userId, string $id): ?Beneficiary
    {
        return Beneficiary::where('user_id', $userId)->where('id', $id)->first();
    }

    public function deleteBeneficiary(string $id): bool
    {
        $beneficiary = Beneficiary::find($id);
        if ($beneficiary) {
            return $beneficiary->delete();
        }
        return false;
    }

    public function getBeneficiaries(string $userId, string $service): \Illuminate\Database\Eloquent\Collection
    {
        return Beneficiary::where('user_id', $userId)->where('service', $service)->get();
    }

    public function searchBeneficiaries(
        string $userId, 
        string $query, 
        ?string $service = null
    ): \Illuminate\Database\Eloquent\Collection {
        $searchTerm = "%{$query}%";
        
        return Beneficiary::when($service, function ($q) use ($service) {
                $q->where('service', $service);
            })
            ->where('user_id', $userId)
            ->where(function ($q) use ($searchTerm) {
                if (config('database.default') === 'pgsql') {
                    // PostgreSQL JSON search syntax
                    $q->whereRaw("payload::json->>'username' LIKE ?", [$searchTerm])
                      ->orWhereRaw("payload::json->>'name' LIKE ?", [$searchTerm])
                      ->orWhereRaw("payload::json->>'email' LIKE ?", [$searchTerm])
                      ->orWhereRaw("payload::json->>'account_number' LIKE ?", [$searchTerm]);
                } else {
                    // MySQL/MariaDB JSON search syntax
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.username')) LIKE ?", [$searchTerm])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.name')) LIKE ?", [$searchTerm])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.email')) LIKE ?", [$searchTerm])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.account_number')) LIKE ?", [$searchTerm]);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }
}