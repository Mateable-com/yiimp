# Hashpower Rental System

Yiimp includes a built-in stratum-to-stratum proxy system that allows users to rent hashpower from the pool and redirect it to external pools.

## How it Works

1.  **Mining Capacity:** The pool has miners connected to various algorithms.
2.  **Rental Jobs:** Renters create jobs specifying an algorithm, a target pool (stratum URL), and a maximum price they are willing to pay.
3.  **Activation:** When the pool's internal profitability for an algorithm drops below a renter's maximum price, the system "activates" the rental job.
4.  **Redirection:** A portion of the pool's miners (those who support extranonce subscription or reconnection) are redirected to the renter's target pool.
5.  **Payment:**
    *   **Renters** pay in BTC from their on-pool balance. They are charged in real-time based on the actual shares submitted to their target pool.
    *   **Miners** receive their share of the renter's BTC payments, distributed among all miners of that algorithm on the pool. This effectively boosts the algorithm's profitability.

## Configuration

To enable renting, ensure the following is set in `serverconfig.php`:

```php
define('YAAMP_RENTAL', true);
```

You should also define the rental fee:

```php
define('YAAMP_FEES_RENTING', 2); // 2% fee
```

## For Renters

1.  **Register:** Go to `/renting` on the pool website and click "Register". You will receive a unique Bitcoin deposit address.
2.  **Deposit:** Send BTC to your deposit address. A minimum deposit (e.g., 0.001 BTC) is recommended.
3.  **Create Job:** Click "New Job" and enter:
    *   **Algo:** The algorithm you want to rent.
    *   **Server:** The stratum URL of your target pool (e.g., `stratum.slushpool.com:3333`).
    *   **Username/Password:** Your credentials on the target pool.
    *   **Max Price:** The maximum you are willing to pay in mBTC/MH/day (or GH/day for fast algos).
    *   **Max Hashrate:** (Optional) Limit the amount of power you want to rent.
4.  **Start:** Click the "Play" button to enable the job. It will activate automatically when the price is right.

## Technical Details

*   **Stratum Proxy:** The stratum server handles the connection to the external pool and maps internal miners to it.
*   **Balance Management:** The PHP backend (`BackendRentingUpdate`) periodically checks renter balances and job conditions.
*   **Payouts:** `BackendRentingPayout` creates dummy blocks and earnings to distribute renter funds to miners.
*   **Deposit Checking:** `BackendUpdateDeposit` monitors the pool's BTC wallet for incoming renter deposits.

## Units

*   Prices are generally in **mBTC/MH/day**.
*   For high-speed algorithms like SHA256 and Blake, units are in **mBTC/GH/day**.
*   Renter balances and miner earnings are tracked in **BTC**.
