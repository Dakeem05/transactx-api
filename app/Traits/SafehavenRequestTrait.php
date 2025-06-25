<?php

namespace App\Traits;

use App\Services\External\SafehavenService;
use Exception;
use Illuminate\Support\Facades\Log;

trait SafehavenRequestTrait
{
    protected ?SafehavenService $safehavenService = null;
    
    protected function getSafehavenService(): SafehavenService
    {
        return $this->safehavenService ??= resolve(SafehavenService::class);
    }

    public function getAllBillers()
    {
        return $this->getSafehavenService()->getBillers();
    }

    /**
     * The various billers are:
     * cable tv bills - for paying cable TV bills
     * utility bills - for electricity, water, etc
     * mobile recharge - for airtime.
     * data - for data.
     */
    public function getSpecificServiceBiller(string $service): array|null
    {
        if (! in_array($service, [
            'UTILITY', 'AIRTIME', 'DATA', 'CABLE-TV'
        ])) {
            return null;
        }
        
        $allBillers = $this->getAllBillers();
        if ($allBillers['statusCode'] != 200) {
            throw new Exception($allBillers['message']);
        }
        $specificBillerId = "";
        foreach ($allBillers['data'] as $key => $value) {
            if (strtoupper($value['identifier']) == strtoupper(str_replace('-', '', $service))) {
                $specificBillerId = $value['_id'];
                break;
            }
        }
        if (empty($specificBillerId)) {
            return null;
        }
        
        return $this->getSafehavenService()->getBillerById($specificBillerId)['data'];
    }

    public function getBillerCategory (string $billerId): array|null
    {
        $category = $this->getSafehavenService()->getBillerCategory($billerId);
        if ($category['statusCode'] != 200) {
            Log::error('getBillerCategory: Failed to get biller category. Reason: ' . $category['message']);
            throw new Exception($category['message']);
        }
        return $category['data'];
    }

    public function getBillerCategoryProduct (string $categoryId): array|null
    {
        $product = $this->getSafehavenService()->getBillerCategoryProduct($categoryId);
        if ($product['statusCode'] != 200) {
            Log::error('getBillerCategoryProduct: Failed to get biller category product. Reason: ' . $product['message']);
            throw new Exception($product['message']);
        }
        
        return $product['data'];
    }

    public function verifyBillerCategoryNumber (string $categoryId, string $number): array|null
    {
        $data = $this->getSafehavenService()->verifyBillerCategoryNumber($categoryId, $number);
        if ($data['statusCode'] != 200) {
            Log::error('verifyBillerCategoryNumber: Failed to verify biller category number. Reason: ' . $data['message']);
            throw new Exception($data['message']);
        }
        
        return $data['data'];
    }

    public function purchaseService (array $data, string $service): array|null
    {
        if (! in_array($service, [
            'UTILITY', 'AIRTIME', 'DATA', 'CABLE-TV'
        ])) {
            return null;
        }

        $response = $this->getSafehavenService()->purchaseService($data, strtolower($service));
        if ($response['statusCode'] != 200) {
            Log::error('purchaseService: Failed to get Purchase Service. Reason: ' . $response['message']);
            throw new Exception($response['message']);
        }
        
        return $response['data'];
    }

    public function getPurchaseTransaction (string $id): array|null
    {
        $response = $this->getSafehavenService()->getPurchaseTransaction($id);
        if ($response['statusCode'] != 200) {
            Log::error('getPurchaseTransaction: Failed to get purchase transaction. Reason: ' . $response['message']);
            throw new Exception($response['message']);
        }
        
        return $response['data'];
    }
}