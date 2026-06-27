-- ============================================================================
-- SIMPLE SQL COMMANDS TO OPTIMIZE PUNCH APPROVAL (10 LAKH+ RECORDS)
-- Run these commands one by one in your MySQL client
-- ============================================================================

-- Step 1: Drop old inefficient indexes (ignore errors if they don't exist)
-- -------------------------------------------------------------------------
ALTER TABLE scan_file DROP INDEX idx_punch_date;
ALTER TABLE scan_file DROP INDEX idx_approve_date;
ALTER TABLE scan_file DROP INDEX idx_reject_date;
ALTER TABLE scan_file DROP INDEX idx_punch_approval_queue;


-- Step 2: Create new covering indexes for each tab
-- -------------------------------------------------------------------------

-- Pending tab (most important - loads first)
CREATE INDEX idx_pending_covering 
ON scan_file (Group_Id, Is_Deleted, File_Punched, File_Approved, Is_Rejected, Punch_Date DESC);

-- Approved tab
CREATE INDEX idx_approved_covering 
ON scan_file (Group_Id, Is_Deleted, File_Punched, File_Approved, Punch_Date DESC);

-- Rejected tab
CREATE INDEX idx_rejected_covering 
ON scan_file (Group_Id, Is_Deleted, File_Punched, Is_Rejected, Punch_Date DESC);


-- Step 3: Add indexes for JOIN optimization
-- -------------------------------------------------------------------------
CREATE INDEX idx_punch_by ON scan_file (Punch_By);
CREATE INDEX idx_approve_by ON scan_file (Approve_By);


-- Step 4: Update table statistics (very important!)
-- -------------------------------------------------------------------------
ANALYZE TABLE scan_file;


-- Step 5: Verify indexes were created
-- -------------------------------------------------------------------------
SHOW INDEX FROM scan_file WHERE Key_name LIKE 'idx_%';


-- ============================================================================
-- TEST QUERY - Should now use idx_pending_covering and be FAST
-- ============================================================================
EXPLAIN SELECT s.Scan_Id, s.File, s.Punch_Date
FROM scan_file as s 
WHERE s.Group_Id IN (1,2,3,4,5)
  AND s.Is_Deleted = 'N'
  AND s.File_Punched = 'Y'
  AND s.File_Approved = 'N'
  AND (s.Is_Rejected IS NULL OR s.Is_Rejected = 'N')
ORDER BY s.Punch_Date DESC
LIMIT 10;

-- Expected result: 
-- type: range or ref
-- key: idx_pending_covering
-- rows: < 100
