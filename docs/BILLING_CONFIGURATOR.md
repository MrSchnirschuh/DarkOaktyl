# Billing System - Resource-Based Configurator

## Overview

The resource-based configurator is a new billing system feature that allows dynamic product creation based on customer-selected resources, eliminating the need to create separate products for every hardware tier and software combination.

## Problem Solved

Previously, hosting providers had to create many individual products (e.g., "Minecraft Paper 2GB", "Minecraft Paper 4GB", "Minecraft Forge 2GB", etc.). This made:
- Product management time-consuming
- Price changes difficult (need to update each product individually)
- Adding new tiers or software types tedious

The configurator solves this by:
- Allowing customers to select resources dynamically
- Centralizing pricing in reusable configurations
- Enabling flexible pricing factors based on package size and duration

## Architecture

### Database Schema

#### `pricing_configurations`
Stores the base pricing rules for resources:
- `cpu_price`: Price per 1% CPU
- `memory_price`: Price per MB memory
- `disk_price`: Price per MB disk
- `backup_price`: Price per backup slot
- `database_price`: Price per database
- `allocation_price`: Price per port allocation
- Package size factors (small/medium/large)
- Package size thresholds

#### `pricing_durations`
Stores duration-based pricing factors:
- `duration_days`: Billing period length
- `price_factor`: Multiplier applied to base price (e.g., 0.9 for 10% discount)

#### Category Updates
- `pricing_configuration_id`: Links to pricing configuration
- `use_configurator`: Boolean to enable configurator mode

## Usage

### For Administrators

#### Creating a Pricing Configuration

1. Navigate to **Admin > Billing > Pricing**
2. Click **New Configuration**
3. Fill in the form:
   - **Name**: Descriptive name (e.g., "Standard Pricing")
   - **Status**: Enable/disable the configuration
   - **Resource Prices**: Set price per unit for each resource
   - **Package Size Factors**: 
     - Small threshold (MB): Memory threshold for small packages
     - Small factor: Price multiplier for small packages
     - Medium factor: Price multiplier for medium packages
     - Large threshold (MB): Memory threshold for large packages
     - Large factor: Price multiplier for large packages (e.g., 0.95 for 5% discount)
   - **Duration Pricing**: Add billing periods with custom factors

Example package size configuration:
```
Small Threshold: 2048 MB (2GB)
Small Factor: 1.0 (no change)
Medium Factor: 1.0 (no change)
Large Threshold: 8192 MB (8GB)
Large Factor: 0.95 (5% discount for large packages)
```

#### Enabling Configurator for a Category

1. Create or edit a category
2. Set **Pricing Configuration** to your desired configuration
3. Enable **Use Configurator** mode
4. Save the category

### For Customers

When viewing a category with configurator enabled:

1. Select desired resources using sliders/inputs:
   - CPU (%)
   - Memory (MB)
   - Disk Space (MB)
   - Databases
   - Backups
   - Port Allocations

2. Choose billing period:
   - Monthly (30 days)
   - Quarterly (90 days)
   - Semi-Annually (180 days)
   - Annually (365 days)

3. View real-time price calculation

4. Click **Proceed to Checkout**

## Price Calculation

The final price is calculated as:

```
Base Price = (cpu × cpu_price) + (memory × memory_price) + 
             (disk × disk_price) + (backups × backup_price) + 
             (databases × database_price) + (allocations × allocation_price)

Package Factor = small_factor | medium_factor | large_factor
                 (based on memory threshold)

Duration Factor = price_factor from selected duration

Final Price = Base Price × Package Factor × Duration Factor
```

### Example

Configuration:
- CPU: $0.001 per %
- Memory: $0.0001 per MB
- Disk: $0.00001 per MB
- Backups: $0.50 each
- Databases: $0.25 each
- Allocations: $0.10 each
- Large package factor: 0.95 (for >8GB)
- Annual factor: 0.85 (15% discount)

Customer selects:
- 200% CPU
- 10240 MB (10GB) memory
- 20480 MB (20GB) disk
- 2 databases
- 1 backup
- 1 allocation

Calculation:
```
Base Price = (200 × 0.001) + (10240 × 0.0001) + (20480 × 0.00001) + 
             (1 × 0.50) + (2 × 0.25) + (1 × 0.10)
           = 0.20 + 1.024 + 0.2048 + 0.50 + 0.50 + 0.10
           = $2.53

With large package factor: $2.53 × 0.95 = $2.40
With annual duration: $2.40 × 0.85 = $2.04 per month (billed annually)
```

## API Endpoints

### Admin API

- `GET /api/application/billing/pricing` - List pricing configurations
- `POST /api/application/billing/pricing` - Create configuration
- `GET /api/application/billing/pricing/{id}` - View configuration
- `PATCH /api/application/billing/pricing/{id}` - Update configuration
- `DELETE /api/application/billing/pricing/{id}` - Delete configuration

### Client API

- `POST /api/client/billing/calculate-price` - Calculate price for resources

Request body:
```json
{
  "pricing_configuration_id": 1,
  "cpu": 200,
  "memory": 10240,
  "disk": 20480,
  "backups": 1,
  "databases": 2,
  "allocations": 1,
  "duration_days": 365
}
```

Response:
```json
{
  "base_price": 2.53,
  "duration_factor": 0.85,
  "final_price": 2.04,
  "currency": "USD"
}
```

## Migration from Fixed Products

1. Create a pricing configuration matching your current pricing structure
2. Enable configurator mode on the category
3. Keep existing fixed products for backward compatibility
4. New customers use the configurator; existing customers keep their plans
5. Gradually migrate customers to the new system (optional)

## Best Practices

1. **Pricing Strategy**:
   - Start with simple 1.0 factors for all package sizes
   - Adjust based on market research and costs
   - Use duration discounts to encourage longer commitments

2. **Package Size Factors**:
   - Small factor > 1.0: Charge premium for small packages
   - Large factor < 1.0: Offer bulk discounts
   - Consider operational costs when setting factors

3. **Duration Pricing**:
   - Monthly: 1.0 (base price)
   - Quarterly: 0.98 (2% discount)
   - Semi-Annual: 0.95 (5% discount)
   - Annual: 0.90 (10% discount)

4. **Testing**:
   - Create test configuration before going live
   - Verify calculations match expected pricing
   - Test with various resource combinations

## Troubleshooting

### Configurator not showing for category

- Verify `use_configurator` is enabled
- Check that `pricing_configuration_id` is set
- Ensure pricing configuration is enabled

### Prices seem incorrect

- Check all resource prices in configuration
- Verify package size thresholds and factors
- Review duration factors
- Test calculation manually

### Server creation fails with renewal date error

Fixed in this PR - admin-created servers now properly support null renewal dates for free servers.

## Future Enhancements

Potential improvements:
- Node-specific pricing (different prices per node)
- Resource limits based on user tier
- Dynamic pricing based on demand
- Promotional pricing codes
- Volume discounts for multiple servers
- Custom resource bundles
