# YiiMP Renter System & Symbol Alias Guide (2026 Modernization)

This document explains how the Renter (Rental) system works in YiiMP, how to keep your funds separate using the **Symbol Alias** feature, and how miners are paid.

---

## 1. What is the Renter System?

The Renter system allows users ("Renters") to buy hashing power from your pool's miners. 
- **Renters** pay in Bitcoin (BTC) to have the pool's hashpower redirected to their own chosen pool/stratum.
- **Miners** receive a portion of that BTC as a "bonus" for providing their hashrate.
- **Pool Owner** takes a percentage fee from the rental transaction.

## 2. Separation of Funds (The Symbol Alias)

By default, YiiMP uses the main pool wallet (`BTC`) for everything. To keep your "Pool Funds" separate from "Renter Funds," we use a **Symbol Alias**.

### How to set up the separate wallet:
1. **Configure a 2nd Wallet:** Run a separate `bitcoind` instance (or a compatible wallet) on a different port or server.
2. **Add Coin to DB:** In the YiiMP Admin Panel, add a new coin:
   - **Symbol:** `BTCR` (or any alias you choose).
   - **RPC Credentials:** Point these to your **2nd** wallet.
   - **Enable:** Set to "Installed".
3. **Enable Alias in Config:** Add this to your `/root/yiimp/web/serverconfig.php`:
   ```php
   define('YAAMP_RENTER_COIN', 'BTCR'); // Tells the renter system to use the BTCR wallet
   ```

**Result:** When a renter deposits money, it goes into your **2nd wallet**. When the pool pays miners, it uses the main **1st wallet**. They never mix.

---

## 3. How Miners Get Paid (The "Bonus")

### **How do miners enter their BTC address for the bonus?**

We have added a new feature that allows miners to get their Renter Bonus directly in BTC, even if they are mining another coin (like LTC or DOGE).

**Method 1: Using the Config Generator**
On the pool's homepage, there is now a field called **"Renter BTC Payout (Optional)"**. 
- If a miner enters their BTC address there, the generator will add `,r=YOUR_BTC_ADDRESS` to their password (`-p`) field.

**Method 2: Manual Config**
Miners can manually add the `r=` parameter to their password in their mining software.
- **Example:** `-p c=LTC,r=1YourBtcAddressHere`

### **Why use the `r=` parameter?**
1. **Direct BTC:** The miner receives the bonus in the original BTC paid by the renter.
2. **No conversion:** The pool doesn't need to have enough LTC/DOGE to pay the bonus; it uses the BTC already in the renter wallet.
3. **Better for you:** This reduces the "Negative Balance" problem on your pool wallets.

### **Do miners need to log in to the Renter?**
**No.** Miners never need to log in to the renter system. 

The process is completely automatic:
1. **The Miner:** Connects their rig to your pool using their normal wallet address as the username (e.g., a LTC address or BTC address).
2. **The System:** When a Renter's job is active, the system calculates how much BTC the Renter owes based on the difficulty of the shares the miners are finding.
3. **The Distribution:** The pool's background process (`BackendRentingPayout`) takes that BTC from the Renter's balance and divides it among **all miners** currently mining that specific algorithm.
4. **The Earning:** The miner sees a "bonus" in their balance. 
   - If they are mining a coin like Litecoin, they get their Litecoin PLUS a small amount of the Renter's BTC.
   - If they have "Auto-Exchange" off, they get the BTC as a separate balance.
   - If they have "Auto-Exchange" on, the BTC is added to their total payout calculation.

---

## 4. The Renter Workflow

1. **Login:** A Renter logs in using a Bitcoin address (this is their account ID).
2. **Deposit:** They send BTC (or your `BTCR` alias) to the address shown in their "Settings" page.
3. **Create Job:** They enter a target Stratum URL (e.g., `stratum+tcp://anotherpool.com:3333`), a username, and a "Max Price" they are willing to pay.
4. **Activation:** If the Renter's price is higher than the current "normal" mining profit, the pool automatically redirects a portion of the miners to work on the Renter's job.
5. **Real-time Payment:** As miners submit shares to the Renter's target pool, the Renter's balance is deducted in real-time.

---

## 5. Summary of Required Settings

Ensure these are in your `serverconfig.php`:

```php
// Enable the system
define('YAAMP_RENTAL', true);

// Set the separate wallet alias (The feature we just added)
define('YAAMP_RENTER_COIN', 'BTCR'); 

// Set your fees (e.g., 2%)
define('YAAMP_FEES_RENTING', 2);

// Set withdrawal fees for renters
define('YAAMP_TXFEE_RENTING_WD', 0.0001);
```

---
*Note: The Renter system is independent of the "Auto-Exchange" feature. You do NOT need auto-exchange enabled for Renters to work.*
