-- Expand workers.password column to support long merged mining passwords
-- Format: c=COIN,mc=COIN,m=AUX1:ADDR1,m=AUX2:ADDR2,... supports ~22 aux coins
ALTER TABLE workers MODIFY password VARCHAR(1024);
