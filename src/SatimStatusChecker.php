<?php

namespace PiteurStudio;

use PiteurStudio\Exception\SatimInvalidDataException;

trait SatimStatusChecker
{
    protected ?array $confirmPaymentData = null;

    /**
     * Get the order status success message
     *
     * @throws SatimInvalidDataException
     */
    public function getSuccessMessage(): string
    {
        return $this->getConfirmPaymentData()['params']['respCode_desc'] ?? ($this->getConfirmPaymentData()['actionCodeDescription'] ?? 'Payment was successful');
    }

    /**
     * Get the order status error message
     *
     * @throws SatimInvalidDataException
     */
    public function getErrorMessage(): string
    {
        if ($this->isRejected()) {
            return '« Votre transaction a été rejetée/ Your transaction was rejected/ تم رفض معاملتك »';
        }

        if ($this->isRefunded()) {
            return 'Payment was refunded';
        }

        return $this->getConfirmPaymentData()['params']['respCode_desc'] ?? ($this->getConfirmPaymentData()['actionCodeDescription'] ?? 'Payment failed');
    }

    /**
     * Get the response data from the last request.
     *
     * @throws SatimInvalidDataException
     */
    public function getConfirmPaymentData(): array
    {
        if (! isset($this->confirmPaymentData)) {
            throw new SatimInvalidDataException('No data available : call getOrderStatus() or confirmOrder() first.');
        }

        return $this->confirmPaymentData;
    }

    /**
     * Check if the response data is available before any status checks.
     *
     * @throws SatimInvalidDataException
     */
    protected function ensureDataIsAvailable(): void
    {
        if (! isset($this->response_data)) {
            throw new SatimInvalidDataException('No data available: call confirmOrder() or getOrderStatus() first.');
        }
    }

    /**
     * Check if the transaction was rejected.
     *
     * @throws SatimInvalidDataException
     */
    public function isRejected(): bool
    {
        $this->ensureDataIsAvailable();

        return (isset($this->response_data['params']['respCode']) && $this->response_data['params']['respCode'] == '00')
            && $this->response_data['ErrorCode'] == '0'
            && $this->response_data['OrderStatus'] == '3';
    }

    /**
     * Check if the transaction was successful.
     *
     * @throws SatimInvalidDataException
     */
    public function isSuccessful(): bool
    {
        $this->ensureDataIsAvailable();

        return isset($this->response_data['OrderStatus'])
            && ($this->response_data['OrderStatus'] == '2' || $this->response_data['OrderStatus'] == '0');
    }

    /**
     * Check if the transaction failed.
     *
     * @throws SatimInvalidDataException
     */
    public function isFailed(): bool
    {
        $this->ensureDataIsAvailable();

        return ! $this->isSuccessful();
    }

    /**
     * Check if the transaction was refunded.
     *
     * @throws SatimInvalidDataException
     */
    public function isRefunded(): bool
    {
        $this->ensureDataIsAvailable();

        return isset($this->response_data['OrderStatus']) && $this->response_data['OrderStatus'] == '4';
    }

    /**
     * Check if the transaction was cancelled.
     *
     * @throws SatimInvalidDataException
     */
    public function isCancelled(): bool
    {
        $this->ensureDataIsAvailable();

        return isset($this->response_data['actionCode']) && $this->response_data['actionCode'] == '10';
    }

    /**
     * Check if the transaction expired.
     *
     * @throws SatimInvalidDataException
     */
    public function isExpired(): bool
    {
        $this->ensureDataIsAvailable();

        return isset($this->response_data['actionCode']) && $this->response_data['actionCode'] == '-2007';
    }
}
