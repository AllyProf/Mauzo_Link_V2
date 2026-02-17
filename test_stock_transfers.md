# Stock Transfers Testing Guide

## Quick Database Checks

### Check Warehouse Stock
```sql
SELECT 
    p.name as product_name,
    pv.measurement,
    pv.packaging,
    sl.quantity as warehouse_stock,
    sl.average_buying_price,
    sl.selling_price
FROM stock_locations sl
JOIN product_variants pv ON sl.product_variant_id = pv.id
JOIN products p ON pv.product_id = p.id
WHERE sl.location = 'warehouse'
AND sl.user_id = YOUR_USER_ID
AND sl.quantity > 0;
```

### Check Counter Stock
```sql
SELECT 
    p.name as product_name,
    pv.measurement,
    pv.packaging,
    sl.quantity as counter_stock,
    sl.average_buying_price,
    sl.selling_price
FROM stock_locations sl
JOIN product_variants pv ON sl.product_variant_id = pv.id
JOIN products p ON pv.product_id = p.id
WHERE sl.location = 'counter'
AND sl.user_id = YOUR_USER_ID;
```

### Check Stock Transfers
```sql
SELECT 
    st.transfer_number,
    st.status,
    st.quantity_requested,
    st.total_units,
    st.created_at,
    st.approved_at,
    u1.name as requested_by,
    u2.name as approved_by
FROM stock_transfers st
LEFT JOIN users u1 ON st.requested_by = u1.id
LEFT JOIN users u2 ON st.approved_by = u2.id
WHERE st.user_id = YOUR_USER_ID
ORDER BY st.created_at DESC;
```

### Check Stock Movements
```sql
SELECT 
    sm.movement_type,
    sm.from_location,
    sm.to_location,
    sm.quantity,
    sm.created_at,
    p.name as product_name,
    pv.measurement
FROM stock_movements sm
JOIN product_variants pv ON sm.product_variant_id = pv.id
JOIN products p ON pv.product_id = p.id
WHERE sm.user_id = YOUR_USER_ID
AND sm.movement_type = 'transfer'
ORDER BY sm.created_at DESC;
```

## Testing Workflow

1. **Setup**: Create a stock receipt to add warehouse stock
2. **Create Transfer**: Request transfer from warehouse to counter
3. **Approve Transfer**: Move stock to counter
4. **Verify**: Check that warehouse stock decreased and counter stock increased
5. **Check Movements**: Verify stock movement record was created




