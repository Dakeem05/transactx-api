<?php

namespace App\Traits;

use App\Services\External\SafehavenService;

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
        if (strtolower($allBillers['statusCode']) != 200) {
            return null;
        }
        
        $specificBillerId = "";
        foreach ($allBillers['data'] as $key => $value) {
            if (strtoupper($value['identifier']) == strtoupper($service)) {
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
        if (strtolower($category['statusCode']) != 200) {
            return null;
        }
        
        return $category['data'];
    }

    public function getBillerCategoryProduct (string $categoryId): array|null
    {
        $product = $this->getSafehavenService()->getBillerCategoryProduct($categoryId);
        if (strtolower($product['statusCode']) != 200) {
            return null;
        }
        
        return $product['data'];
    }

    public function purchaseService (array $data, string $service): array|null
    {
        if (! in_array($service, [
            'UTILITY', 'AIRTIME', 'DATA', 'CABLE-TV'
        ])) {
            return null;
        }

        $response = $this->getSafehavenService()->purchaseService($data, strtolower($service));
        if (strtolower($response['statusCode']) != 200) {
            return null;
        }
        
        return $response['data'];
    }
}