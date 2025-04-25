<?php

namespace App\Contracts;

use Illuminate\Http\Client\Response;

/**
 * Interface PaymentGateway
 *
 * Defines the contract for a payment gateway service.
 */
interface PaymentGateway
{
    /**
     * Get the list of banks.
     *
     * @return array An array of bank information (name and code).
     */
    public function getBanks(): array;

    /**
     * Resolve an account number with a bank code.
     *
     * @param string $accountNumber The account number to resolve.
     * @param string $bankCode      The bank code.
     *
     * @return array The resolved account information.
     */
    public function resolveAccount(string $accountNumber, string $bankCode): array;

    /**
     * Initialize a payment transaction.
     *
     * @param float  $amount    The transaction amount.
     * @param string $email     The customer's email.
     * @param string $reference The transaction reference.
     *
     * @return array The initialized transaction details.
     */
    public function initializeTransaction(float $amount, string $email, string $reference): array;

    /**
     * Verify a payment transaction.
     *
     * @param string $reference The transaction reference.
     *
     * @return array The verification result.
     */
    public function verifyTransaction(string $reference): array;

    /**
     * Create a refund for a transaction.
     *
     * @param string $reference The transaction reference.
     *
     * @return array The refund details.
     */
    public function createRefund(string $reference): array;

    /**
     * Create a transfer recipient.
     *
     * @param string $accountNumber The recipient's account number.
     * @param string $bankCode      The recipient's bank code.
     * @param string $name          The recipient's name.
     * @param string $type          The recipient's type.
     * @param string $currency      The currency of the transfer.
     *
     * @return array The created recipient details.
     */
    public function createRecipient(string $accountNumber, string $bankCode, string $name, string $type, string $currency): array;

    /**
     * Create a transfer.
     *
     * @param string $recipientCode The recipient code.
     * @param float  $amount        The transfer amount.
     * @param string $reason        The reason for the transfer.
     * @param string $reference     The reference for the transfer.
     *
     * @return array The created transfer details.
     */
    public function createTransfer(string $recipientCode, string $amount, string $reason, ?string $reference): array;

    /**
     * Create a customer.
     *
     * @param string $firstName The customer's first name.
     * @param string $lastName  The customer's last name.
     * @param string $email     The customer's email.
     * @param string $phone     The customer's phone number.
     *
     * @return array The created customer details.
     */
    public function createCustomer(string $firstName, string $lastName, string $email, string $phone): array;

    /**
     * Validate customer identification.
     *
     * @param string $customerCode   The customer's code.
     * @param string $firstName      The customer's first name.
     * @param string $lastName       The customer's last name.
     * @param string $accountNumber  The customer's account number.
     * @param string $bvn            The customer's BVN.
     * @param string $bankCode       The customer's bank code.
     *
     * @return array The validation result.
     */
    public function validateCustomer(string $customerCode, string $firstName, string $lastName, string $accountNumber, string $bvn, string $bankCode): array;

    /**
     * Create a dedicated virtual account.
     *
     * @param string $customerCode The customer's code.
     *
     * @return array The created dedicated virtual account details.
     */
    public function createDVA(string $customerCode): array;

    /**
     * Requery a dedicated virtual account.
     *
     * @param string $accountNumber The account number.
     * @param string $bankName      The bank name.
     * @param string $date          The date of the query.
     *
     * @return Response The requery result.
     */
    public function requeryDVA(string $accountNumber, string $bankName, string $date): Response;
}
