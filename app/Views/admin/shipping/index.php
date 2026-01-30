<div class="page-header">
    <h1>Shipping Settings</h1>
    <p style="color: var(--admin-text-light);">Manage shipping zones, methods, warehouses, and shipping classes</p>
</div>

<!-- Tabs -->
<div class="tabs" style="margin-bottom: 1.5rem;">
    <a href="#zones" class="tab active" onclick="showShippingTab('zones', this)">Zones & Methods</a>
    <a href="#origins" class="tab" onclick="showShippingTab('origins', this)">Warehouses</a>
    <a href="#classes" class="tab" onclick="showShippingTab('classes', this)">Shipping Classes</a>
</div>

<!-- Zones & Methods Tab -->
<div id="tab-zones" class="tab-content active">
    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 1.5rem;">
        <!-- Zones Column -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Shipping Zones</h3>
                    <button type="button" class="btn btn-sm btn-primary" onclick="showAddZoneForm()">+ Add Zone</button>
                </div>

                <!-- Add Zone Form -->
                <div id="addZoneForm" style="display: none; padding: 1rem; background: var(--admin-bg); border-radius: 6px; margin-bottom: 1rem;">
                    <form action="/admin/shipping/zones/store" method="POST">
                        <?php echo csrfField(); ?>
                        <div class="form-group">
                            <label class="form-label">Zone Name</label>
                            <input type="text" name="name" class="form-input" placeholder="e.g., Europe" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Country Codes</label>
                            <input type="text" name="countries" class="form-input" placeholder="US, CA, GB" required>
                            <small style="color: var(--admin-text-light);">Comma-separated 2-letter codes. Use * for "Rest of World"</small>
                        </div>
                        <div class="form-group">
                            <label class="form-checkbox">
                                <input type="checkbox" name="is_active" value="1" checked>
                                <span>Active</span>
                            </label>
                        </div>
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary btn-sm">Create Zone</button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="hideAddZoneForm()">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Zones List -->
                <?php if (!empty($zones)): ?>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <?php foreach ($zones as $zone): ?>
                            <?php $countries = json_decode($zone['countries'], true) ?: []; ?>
                            <div class="zone-item" style="padding: 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px; background: <?php echo $zone['is_active'] ? '#fff' : '#f5f5f5'; ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?php echo escape($zone['name']); ?></strong>
                                        <?php if (!$zone['is_active']): ?>
                                            <span class="badge badge-secondary" style="font-size: 0.7rem;">Inactive</span>
                                        <?php endif; ?>
                                        <div style="font-size: 0.8rem; color: var(--admin-text-light);">
                                            <?php echo escape(implode(', ', $countries)); ?>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 0.25rem;">
                                        <button type="button" class="btn btn-sm btn-outline" onclick="editZone(<?php echo htmlspecialchars(json_encode($zone)); ?>)">Edit</button>
                                        <form action="/admin/shipping/zones/delete" method="POST" style="display: inline;" onsubmit="return confirm('Delete this zone?')">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="id" value="<?php echo $zone['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--admin-text-light); padding: 1rem;">No shipping zones configured.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Methods Column -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Shipping Methods</h3>
                    <button type="button" class="btn btn-sm btn-primary" onclick="showAddMethodForm()">+ Add Method</button>
                </div>

                <!-- Add Method Form -->
                <div id="addMethodForm" style="display: none; padding: 1rem; background: var(--admin-bg); border-radius: 6px; margin-bottom: 1rem;">
                    <form action="/admin/shipping/methods/store" method="POST">
                        <?php echo csrfField(); ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Zone</label>
                                <select name="zone_id" class="form-input" required>
                                    <option value="">Select zone...</option>
                                    <?php foreach ($zones as $zone): ?>
                                        <option value="<?php echo $zone['id']; ?>"><?php echo escape($zone['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Carrier</label>
                                <select name="carrier" class="form-input">
                                    <option value="usps">USPS</option>
                                    <option value="ups">UPS</option>
                                    <option value="fedex">FedEx</option>
                                    <option value="dhl">DHL</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Method Name</label>
                                <input type="text" name="name" class="form-input" placeholder="Standard Shipping" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Method Code</label>
                                <input type="text" name="method_code" class="form-input" placeholder="standard" value="standard">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Flat Rate ($)</label>
                                <input type="number" name="flat_rate" class="form-input" step="0.01" min="0" placeholder="5.99">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Free Shipping Over ($)</label>
                                <input type="number" name="min_order_free" class="form-input" step="0.01" min="0" placeholder="100">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Delivery Estimate</label>
                            <input type="text" name="delivery_estimate" class="form-input" placeholder="5-7 business days">
                        </div>
                        <div class="form-group">
                            <label class="form-checkbox">
                                <input type="checkbox" name="is_active" value="1" checked>
                                <span>Active</span>
                            </label>
                        </div>
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary btn-sm">Create Method</button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="hideAddMethodForm()">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Methods List grouped by zone -->
                <?php
                $methodsByZone = [];
                foreach ($methods as $method) {
                    $methodsByZone[$method['zone_name']][] = $method;
                }
                ?>
                <?php if (!empty($methodsByZone)): ?>
                    <?php foreach ($methodsByZone as $zoneName => $zoneMethods): ?>
                        <div style="margin-bottom: 1rem;">
                            <h4 style="font-size: 0.9rem; color: var(--admin-text-light); margin-bottom: 0.5rem; padding: 0.5rem; background: var(--admin-bg); border-radius: 4px;"><?php echo escape($zoneName); ?></h4>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                <?php foreach ($zoneMethods as $method): ?>
                                    <div class="method-item" style="padding: 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px; background: <?php echo $method['is_active'] ? '#fff' : '#f5f5f5'; ?>;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div>
                                                <strong><?php echo escape($method['name']); ?></strong>
                                                <span style="font-size: 0.8rem; color: var(--admin-text-light);">(<?php echo strtoupper($method['carrier']); ?>)</span>
                                                <?php if (!$method['is_active']): ?>
                                                    <span class="badge badge-secondary" style="font-size: 0.7rem;">Inactive</span>
                                                <?php endif; ?>
                                                <div style="font-size: 0.85rem; color: var(--admin-text-light); margin-top: 0.25rem;">
                                                    <?php if ($method['flat_rate']): ?>
                                                        <span style="color: var(--admin-primary);">$<?php echo number_format($method['flat_rate'], 2); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($method['min_order_free']): ?>
                                                        <span style="color: #28a745;">(Free over $<?php echo number_format($method['min_order_free'], 0); ?>)</span>
                                                    <?php endif; ?>
                                                    <?php if ($method['delivery_estimate']): ?>
                                                        &bull; <?php echo escape($method['delivery_estimate']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div style="display: flex; gap: 0.25rem;">
                                                <button type="button" class="btn btn-sm btn-outline" onclick="editMethod(<?php echo htmlspecialchars(json_encode($method)); ?>)">Edit</button>
                                                <form action="/admin/shipping/methods/delete" method="POST" style="display: inline;" onsubmit="return confirm('Delete this method?')">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="id" value="<?php echo $method['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--admin-text-light); padding: 1rem;">No shipping methods configured.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Origins/Warehouses Tab -->
<div id="tab-origins" class="tab-content" style="display: none;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Warehouses / Ship-From Locations</h3>
            <button type="button" class="btn btn-sm btn-primary" onclick="showAddOriginForm()">+ Add Warehouse</button>
        </div>

        <!-- Add Origin Form -->
        <div id="addOriginForm" style="display: none; padding: 1rem; background: var(--admin-bg); border-radius: 6px; margin-bottom: 1rem;">
            <form action="/admin/shipping/origins/store" method="POST">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label class="form-label">Location Name</label>
                    <input type="text" name="name" class="form-input" placeholder="Main Warehouse" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Address Line 1</label>
                    <input type="text" name="address_line1" class="form-input" placeholder="123 Main St" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Address Line 2</label>
                    <input type="text" name="address_line2" class="form-input" placeholder="Suite 100">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-input" placeholder="CA" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Postal Code</label>
                        <input type="text" name="postal_code" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Country</label>
                        <select name="country" class="form-input">
                            <option value="US">United States</option>
                            <option value="CA">Canada</option>
                            <option value="GB">United Kingdom</option>
                            <option value="IE">Ireland</option>
                            <option value="AU">Australia</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-input" placeholder="(555) 123-4567">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="is_default" value="1">
                            <span>Default warehouse</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span>Active</span>
                        </label>
                    </div>
                </div>
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary btn-sm">Create Warehouse</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="hideAddOriginForm()">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Origins List -->
        <?php if (!empty($origins)): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
                <?php foreach ($origins as $origin): ?>
                    <div class="origin-card" style="padding: 1rem; border: 1px solid var(--admin-border); border-radius: 8px; background: <?php echo $origin['is_active'] ? '#fff' : '#f5f5f5'; ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                            <div>
                                <strong><?php echo escape($origin['name']); ?></strong>
                                <?php if ($origin['is_default']): ?>
                                    <span class="badge badge-success" style="font-size: 0.7rem;">Default</span>
                                <?php endif; ?>
                                <?php if (!$origin['is_active']): ?>
                                    <span class="badge badge-secondary" style="font-size: 0.7rem;">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--admin-text-light); line-height: 1.5;">
                            <?php echo escape($origin['address_line1']); ?><br>
                            <?php if ($origin['address_line2']): ?>
                                <?php echo escape($origin['address_line2']); ?><br>
                            <?php endif; ?>
                            <?php echo escape($origin['city']); ?>, <?php echo escape($origin['state']); ?> <?php echo escape($origin['postal_code']); ?><br>
                            <?php echo escape($origin['country']); ?>
                            <?php if ($origin['phone']): ?>
                                <br><?php echo escape($origin['phone']); ?>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                            <button type="button" class="btn btn-sm btn-outline" onclick="editOrigin(<?php echo htmlspecialchars(json_encode($origin)); ?>)">Edit</button>
                            <form action="/admin/shipping/origins/delete" method="POST" style="display: inline;" onsubmit="return confirm('Delete this warehouse?')">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="id" value="<?php echo $origin['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--admin-text-light); padding: 1rem;">No warehouses configured. Add your first ship-from location above.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Shipping Classes Tab -->
<div id="tab-classes" class="tab-content" style="display: none;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Shipping Classes</h3>
            <button type="button" class="btn btn-sm btn-primary" onclick="showAddClassForm()">+ Add Class</button>
        </div>

        <p style="color: var(--admin-text-light); font-size: 0.875rem; margin-bottom: 1rem;">
            Shipping classes let you add handling fees for specific types of products (e.g., fragile, oversized).
        </p>

        <!-- Add Class Form -->
        <div id="addClassForm" style="display: none; padding: 1rem; background: var(--admin-bg); border-radius: 6px; margin-bottom: 1rem;">
            <form action="/admin/shipping/classes/store" method="POST">
                <?php echo csrfField(); ?>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Class Name</label>
                        <input type="text" name="name" class="form-input" placeholder="Fragile" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Handling Fee ($)</label>
                        <input type="number" name="handling_fee" class="form-input" step="0.01" min="0" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-input" placeholder="Items requiring extra care">
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>Active</span>
                    </label>
                </div>
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary btn-sm">Create Class</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="hideAddClassForm()">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Classes List -->
        <?php if (!empty($classes)): ?>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <?php foreach ($classes as $class): ?>
                    <div class="class-item" style="padding: 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px; background: <?php echo $class['is_active'] ? '#fff' : '#f5f5f5'; ?>; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong><?php echo escape($class['name']); ?></strong>
                            <?php if ($class['handling_fee'] > 0): ?>
                                <span style="color: var(--admin-primary);">+$<?php echo number_format($class['handling_fee'], 2); ?></span>
                            <?php endif; ?>
                            <?php if (!$class['is_active']): ?>
                                <span class="badge badge-secondary" style="font-size: 0.7rem;">Inactive</span>
                            <?php endif; ?>
                            <?php if ($class['description']): ?>
                                <div style="font-size: 0.8rem; color: var(--admin-text-light);"><?php echo escape($class['description']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; gap: 0.25rem;">
                            <button type="button" class="btn btn-sm btn-outline" onclick="editClass(<?php echo htmlspecialchars(json_encode($class)); ?>)">Edit</button>
                            <form action="/admin/shipping/classes/delete" method="POST" style="display: inline;" onsubmit="return confirm('Delete this class?')">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="id" value="<?php echo $class['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--admin-text-light); padding: 1rem;">No shipping classes configured.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Modals -->
<div id="editZoneModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Edit Shipping Zone</h3>
        <form action="/admin/shipping/zones/update" method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="id" id="editZoneId">
            <div class="form-group">
                <label class="form-label">Zone Name</label>
                <input type="text" name="name" id="editZoneName" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Country Codes</label>
                <input type="text" name="countries" id="editZoneCountries" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" name="is_active" id="editZoneActive" value="1">
                    <span>Active</span>
                </label>
            </div>
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-outline" onclick="closeModal('editZoneModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="editMethodModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <h3>Edit Shipping Method</h3>
        <form action="/admin/shipping/methods/update" method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="id" id="editMethodId">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Zone</label>
                    <select name="zone_id" id="editMethodZone" class="form-input" required>
                        <?php foreach ($zones as $zone): ?>
                            <option value="<?php echo $zone['id']; ?>"><?php echo escape($zone['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Carrier</label>
                    <select name="carrier" id="editMethodCarrier" class="form-input">
                        <option value="usps">USPS</option>
                        <option value="ups">UPS</option>
                        <option value="fedex">FedEx</option>
                        <option value="dhl">DHL</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Method Name</label>
                    <input type="text" name="name" id="editMethodName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Method Code</label>
                    <input type="text" name="method_code" id="editMethodCode" class="form-input">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Flat Rate ($)</label>
                    <input type="number" name="flat_rate" id="editMethodRate" class="form-input" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Free Shipping Over ($)</label>
                    <input type="number" name="min_order_free" id="editMethodFreeOver" class="form-input" step="0.01" min="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Delivery Estimate</label>
                <input type="text" name="delivery_estimate" id="editMethodEstimate" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" name="is_active" id="editMethodActive" value="1">
                    <span>Active</span>
                </label>
            </div>
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-outline" onclick="closeModal('editMethodModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="editOriginModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <h3>Edit Warehouse</h3>
        <form action="/admin/shipping/origins/update" method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="id" id="editOriginId">
            <div class="form-group">
                <label class="form-label">Location Name</label>
                <input type="text" name="name" id="editOriginName" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Address Line 1</label>
                <input type="text" name="address_line1" id="editOriginAddr1" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Address Line 2</label>
                <input type="text" name="address_line2" id="editOriginAddr2" class="form-input">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" id="editOriginCity" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">State</label>
                    <input type="text" name="state" id="editOriginState" class="form-input" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Postal Code</label>
                    <input type="text" name="postal_code" id="editOriginPostal" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Country</label>
                    <select name="country" id="editOriginCountry" class="form-input">
                        <option value="US">United States</option>
                        <option value="CA">Canada</option>
                        <option value="GB">United Kingdom</option>
                        <option value="IE">Ireland</option>
                        <option value="AU">Australia</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" id="editOriginPhone" class="form-input">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_default" id="editOriginDefault" value="1">
                        <span>Default warehouse</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_active" id="editOriginActive" value="1">
                        <span>Active</span>
                    </label>
                </div>
            </div>
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-outline" onclick="closeModal('editOriginModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="editClassModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Edit Shipping Class</h3>
        <form action="/admin/shipping/classes/update" method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="id" id="editClassId">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Class Name</label>
                    <input type="text" name="name" id="editClassName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Handling Fee ($)</label>
                    <input type="number" name="handling_fee" id="editClassFee" class="form-input" step="0.01" min="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <input type="text" name="description" id="editClassDesc" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" name="is_active" id="editClassActive" value="1">
                    <span>Active</span>
                </label>
            </div>
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-outline" onclick="closeModal('editClassModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    max-width: 400px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-content h3 {
    margin-bottom: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 480px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function showShippingTab(tabName, element) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + tabName).style.display = 'block';
    element.classList.add('active');
}

// Show/Hide Forms
function showAddZoneForm() { document.getElementById('addZoneForm').style.display = 'block'; }
function hideAddZoneForm() { document.getElementById('addZoneForm').style.display = 'none'; }
function showAddMethodForm() { document.getElementById('addMethodForm').style.display = 'block'; }
function hideAddMethodForm() { document.getElementById('addMethodForm').style.display = 'none'; }
function showAddOriginForm() { document.getElementById('addOriginForm').style.display = 'block'; }
function hideAddOriginForm() { document.getElementById('addOriginForm').style.display = 'none'; }
function showAddClassForm() { document.getElementById('addClassForm').style.display = 'block'; }
function hideAddClassForm() { document.getElementById('addClassForm').style.display = 'none'; }

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Edit functions
function editZone(zone) {
    document.getElementById('editZoneId').value = zone.id;
    document.getElementById('editZoneName').value = zone.name;
    const countries = JSON.parse(zone.countries);
    document.getElementById('editZoneCountries').value = countries.join(', ');
    document.getElementById('editZoneActive').checked = zone.is_active == 1;
    document.getElementById('editZoneModal').style.display = 'flex';
}

function editMethod(method) {
    document.getElementById('editMethodId').value = method.id;
    document.getElementById('editMethodZone').value = method.zone_id;
    document.getElementById('editMethodCarrier').value = method.carrier;
    document.getElementById('editMethodName').value = method.name;
    document.getElementById('editMethodCode').value = method.method_code || '';
    document.getElementById('editMethodRate').value = method.flat_rate || '';
    document.getElementById('editMethodFreeOver').value = method.min_order_free || '';
    document.getElementById('editMethodEstimate').value = method.delivery_estimate || '';
    document.getElementById('editMethodActive').checked = method.is_active == 1;
    document.getElementById('editMethodModal').style.display = 'flex';
}

function editOrigin(origin) {
    document.getElementById('editOriginId').value = origin.id;
    document.getElementById('editOriginName').value = origin.name;
    document.getElementById('editOriginAddr1').value = origin.address_line1;
    document.getElementById('editOriginAddr2').value = origin.address_line2 || '';
    document.getElementById('editOriginCity').value = origin.city;
    document.getElementById('editOriginState').value = origin.state;
    document.getElementById('editOriginPostal').value = origin.postal_code;
    document.getElementById('editOriginCountry').value = origin.country;
    document.getElementById('editOriginPhone').value = origin.phone || '';
    document.getElementById('editOriginDefault').checked = origin.is_default == 1;
    document.getElementById('editOriginActive').checked = origin.is_active == 1;
    document.getElementById('editOriginModal').style.display = 'flex';
}

function editClass(cls) {
    document.getElementById('editClassId').value = cls.id;
    document.getElementById('editClassName').value = cls.name;
    document.getElementById('editClassFee').value = cls.handling_fee || 0;
    document.getElementById('editClassDesc').value = cls.description || '';
    document.getElementById('editClassActive').checked = cls.is_active == 1;
    document.getElementById('editClassModal').style.display = 'flex';
}

// Close modal on outside click
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});
</script>
