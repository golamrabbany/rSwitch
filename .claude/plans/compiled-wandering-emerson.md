# SIP Account Enhancement: Allow P2P Calls & Allow Call Recording

## Context

The admin needs per-SIP-account control over two features:
1. **P2P Calls (PIN-to-PIN)**: Controls Asterisk `direct_media` — when enabled, RTP flows directly between endpoints bypassing the media server. Default: **Enabled**.
2. **Call Recording**: Controls whether `MixMonitor()` is triggered in the dialplan before `Dial()`. Default: **Disabled**.

Only **Admin** users can see/modify these options. Resellers and Clients get default values automatically.

---

## Files to Create

| File | Purpose |
|------|---------|
| `database/migrations/XXXX_add_p2p_and_recording_to_sip_accounts.php` | Add `allow_p2p` and `allow_recording` boolean columns |

## Files to Modify

| # | File | Changes |
|---|------|---------|
| 1 | `app/Models/SipAccount.php` | Add to `$fillable` and `$casts` |
| 2 | `app/Services/SipProvisioningService.php` | Map `allow_p2p` → `direct_media` in `ps_endpoints` |
| 3 | `app/Http/Controllers/Admin/SipAccountController.php` | Add validation & pass fields in store/update |
| 4 | `resources/views/admin/sip-accounts/create.blade.php` | Add P2P & Recording toggle switches |
| 5 | `resources/views/admin/sip-accounts/edit.blade.php` | Add P2P & Recording toggle switches |
| 6 | `resources/views/admin/sip-accounts/show.blade.php` | Display P2P & Recording status |
| 7 | `app/Http/Controllers/Reseller/SipAccountController.php` | Set defaults in store() |
| 8 | `app/Services/Agi/OutboundCallHandler.php` | Set `RECORD_CALL` channel variable |
| 9 | `app/Services/Agi/InboundCallHandler.php` | Set `RECORD_CALL` channel variable for destination |
| 10 | `docker/asterisk/conf/extensions.conf` | Add MixMonitor logic before Dial |
| 11 | `installer/install.sh` | Update extensions.conf template with recording support |
| 12 | `installer/update.sh` | Add Asterisk config update step + recording directory creation |
| 13 | `database/seeders/DatabaseSeeder.php` | Set allow_p2p/allow_recording on seeded SIP accounts |

---

## Implementation Details

### 1. Migration

```php
// add allow_p2p (default true) and allow_recording (default false)
Schema::table('sip_accounts', function (Blueprint $table) {
    $table->boolean('allow_p2p')->default(true)->after('codec_allow');
    $table->boolean('allow_recording')->default(false)->after('allow_p2p');
});
```

### 2. SipAccount Model (`app/Models/SipAccount.php`)

- Add `'allow_p2p', 'allow_recording'` to `$fillable`
- Add `'allow_p2p' => 'boolean', 'allow_recording' => 'boolean'` to `$casts`

### 3. SipProvisioningService (`app/Services/SipProvisioningService.php`)

In `provision()`, change hardcoded `'direct_media' => 'no'` to:
```php
'direct_media' => $sip->allow_p2p ? 'yes' : 'no',
```

### 4. Admin SipAccountController

**store()**: Add validation rules:
```php
'allow_p2p' => ['nullable'],       // checkbox: present = true
'allow_recording' => ['nullable'], // checkbox: present = true
```
Set values on create:
```php
'allow_p2p' => $request->has('allow_p2p'),
'allow_recording' => $request->has('allow_recording'),
```

**update()**: Same validation. Set values on update:
```php
'allow_p2p' => $request->has('allow_p2p'),
'allow_recording' => $request->has('allow_recording'),
```

### 5. Admin Create/Edit Views

Add a new **"Call Features"** card between Authentication and Caller ID cards with two toggle switches:

- **Allow P2P Calls** — toggle switch, default ON in create
- **Allow Call Recording** — toggle switch, default OFF in create

### 6. Admin Show View

Add P2P and Recording status badges in the SIP Configuration card.

### 7. Reseller SipAccountController

In `store()`, set defaults (no form fields exposed):
```php
'allow_p2p' => true,
'allow_recording' => false,
```

Client controller has no create — clients inherit from creation.

### 8. OutboundCallHandler AGI

After SIP account is validated (line ~52), set recording variable:
```php
if ($sipAccount->allow_recording) {
    $agi->setVariable('RECORD_CALL', '1');
}
```

### 9. InboundCallHandler AGI

When routing to a SIP account destination, check destination SIP account's recording flag:
```php
if ($sipAccount->allow_recording) {
    $agi->setVariable('RECORD_CALL', '1');
}
```

### 10. Asterisk Dialplan (`docker/asterisk/conf/extensions.conf`)

Add MixMonitor before Dial in both outbound and internal paths:

**Outbound (external) path** — after CLI set, before Dial:
```
same => n,GotoIf($["${RECORD_CALL}" != "1"]?skip_rec_ext)
same => n,MixMonitor(/var/spool/asterisk/recording/${CDR_UUID}.wav,b)
same => n(skip_rec_ext),Dial(${ROUTE_DIAL_STRING},${ROUTE_DIAL_TIMEOUT},gT)
```

**Internal path** — same pattern before internal Dial:
```
same => n(internal),NoOp(Internal SIP-to-SIP call)
same => n,Set(CALLERID(name)=${ROUTE_CLI_NAME})
same => n,Set(CALLERID(num)=${ROUTE_CLI_NUM})
same => n,GotoIf($["${RECORD_CALL}" != "1"]?skip_rec_int)
same => n,MixMonitor(/var/spool/asterisk/recording/${CDR_UUID}.wav,b)
same => n(skip_rec_int),Dial(${ROUTE_DIAL_STRING},${ROUTE_DIAL_TIMEOUT},gT)
```

**Inbound `[from-trunk]`** — same pattern before Dial:
```
same => n,GotoIf($["${RECORD_CALL}" != "1"]?skip_rec_in)
same => n,MixMonitor(/var/spool/asterisk/recording/${CDR_UUID}.wav,b)
same => n(skip_rec_in),Dial(${ROUTE_DIAL_STRING},${ROUTE_DIAL_TIMEOUT},gT)
```

### 11. Installer install.sh

Update the `configure_asterisk()` function's extensions.conf template to include the MixMonitor recording logic (matching the docker version).

Add recording directory creation:
```bash
mkdir -p /var/spool/asterisk/recording
chown asterisk:asterisk /var/spool/asterisk/recording
```

### 12. Installer update.sh

Add to `update_application()` after migrations:
```bash
# Create recording directory if not exists
mkdir -p /var/spool/asterisk/recording
chown asterisk:asterisk /var/spool/asterisk/recording

# Update Asterisk extensions.conf for recording support
update_asterisk_config
```

Add `update_asterisk_config()` function that patches extensions.conf if recording support is missing (checks for `RECORD_CALL` string, backs up and rewrites if not present).

### 13. Database Seeder

In the SIP account creation loop, add randomized values:
```php
'allow_p2p' => (bool) rand(0, 1),
'allow_recording' => rand(1, 10) > 7, // 30% have recording
```

---

## Verification

1. Run `./vendor/bin/sail artisan migrate` — new columns added
2. Run `./vendor/bin/sail artisan migrate:fresh --seed` — seeds complete with new fields
3. Admin Create SIP → toggle switches visible, defaults correct (P2P on, Recording off)
4. Admin Edit SIP → toggle switches reflect saved values
5. Admin Show SIP → P2P and Recording status displayed
6. Reseller Create SIP → no toggle switches visible, defaults applied
7. Client Edit SIP → no toggle switches visible
8. Check Asterisk provisioning: `docker exec rswitch-asterisk-1 asterisk -rx "pjsip show endpoint 100001"` — verify `direct_media` matches `allow_p2p`
