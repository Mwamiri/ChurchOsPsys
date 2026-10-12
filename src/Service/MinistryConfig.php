<?php

namespace App\Service;

/**
 * Defines all CRM areas and their field configurations.
 * Mirrors CI4's Config\Ministry.php.
 */
class MinistryConfig
{
    public const AREAS = [
        'members'       => ['label' => 'Members',        'icon' => 'users',       'table' => 'members'],
        'leaders'       => ['label' => 'Leaders',         'icon' => 'star',        'table' => 'leaders'],
        'new_believers' => ['label' => 'New Believers',   'icon' => 'heart',       'table' => 'new_believers'],
        'visitors'      => ['label' => 'Visitors',        'icon' => 'eye',         'table' => 'visitors'],
        'households'    => ['label' => 'Households',      'icon' => 'home',        'table' => 'households'],
        'groups'        => ['label' => 'Groups',          'icon' => 'users-group', 'table' => 'groups'],
        'partners'      => ['label' => 'Partners',        'icon' => 'handshake',   'table' => 'partners'],
        'missions'      => ['label' => 'Missions',        'icon' => 'globe',       'table' => 'missions'],
        'diaspora'      => ['label' => 'Diaspora',        'icon' => 'map-pin',     'table' => 'diaspora'],
        'business_forum'=> ['label' => 'Business Forum',  'icon' => 'briefcase',   'table' => 'business_forum'],
        'church_assets' => ['label' => 'Church Assets',   'icon' => 'building',    'table' => 'church_assets'],
    ];

    public const FIELDS = [
        'members' => [
            ['col' => 'full_name',        'label' => 'Full Name',        'type' => 'text',   'required' => true],
            ['col' => 'first_name',       'label' => 'First Name',       'type' => 'text'],
            ['col' => 'surname',          'label' => 'Surname',          'type' => 'text'],
            ['col' => 'gender',           'label' => 'Gender',           'type' => 'select', 'options' => ['male','female','other']],
            ['col' => 'dob',              'label' => 'Date of Birth',    'type' => 'date'],
            ['col' => 'id_number',        'label' => 'ID Number',        'type' => 'text'],
            ['col' => 'phone',            'label' => 'Phone',            'type' => 'tel'],
            ['col' => 'alt_phone',        'label' => 'Alt Phone',        'type' => 'tel'],
            ['col' => 'email',            'label' => 'Email',            'type' => 'email'],
            ['col' => 'address',          'label' => 'Address',          'type' => 'textarea'],
            ['col' => 'county',           'label' => 'County',           'type' => 'text'],
            ['col' => 'conversion_date',  'label' => 'Conversion Date',  'type' => 'date'],
            ['col' => 'baptism_date',     'label' => 'Baptism Date',     'type' => 'date'],
            ['col' => 'membership_date',  'label' => 'Membership Date',  'type' => 'date'],
            ['col' => 'membership_no',    'label' => 'Membership No.',   'type' => 'text'],
            ['col' => 'marital_status',   'label' => 'Marital Status',   'type' => 'select', 'options' => ['single','married','widowed','divorced']],
            ['col' => 'occupation',       'label' => 'Occupation',       'type' => 'text'],
            ['col' => 'emergency_contact','label' => 'Emergency Contact','type' => 'text'],
            ['col' => 'notes',            'label' => 'Notes',            'type' => 'textarea'],
        ],
        'leaders' => [
            ['col' => 'full_name',       'label' => 'Full Name',       'type' => 'text', 'required' => true],
            ['col' => 'phone',           'label' => 'Phone',           'type' => 'tel'],
            ['col' => 'email',           'label' => 'Email',           'type' => 'email'],
            ['col' => 'ministry_role',   'label' => 'Ministry Role',   'type' => 'text'],
            ['col' => 'appointment_date','label' => 'Appointment Date','type' => 'date'],
            ['col' => 'ordained',        'label' => 'Ordained?',       'type' => 'checkbox'],
            ['col' => 'ordination_date', 'label' => 'Ordination Date', 'type' => 'date'],
            ['col' => 'scope',           'label' => 'Scope',           'type' => 'select', 'options' => ['local','regional','national','international']],
            ['col' => 'bio',             'label' => 'Biography',       'type' => 'textarea'],
            ['col' => 'notes',           'label' => 'Notes',           'type' => 'textarea'],
        ],
        'new_believers' => [
            ['col' => 'full_name',        'label' => 'Full Name',        'type' => 'text', 'required' => true],
            ['col' => 'first_name',       'label' => 'First Name',       'type' => 'text'],
            ['col' => 'surname',          'label' => 'Surname',          'type' => 'text'],
            ['col' => 'gender',           'label' => 'Gender',           'type' => 'select', 'options' => ['male','female','other']],
            ['col' => 'phone',            'label' => 'Phone',            'type' => 'tel'],
            ['col' => 'address',          'label' => 'Address',          'type' => 'textarea'],
            ['col' => 'salvation_date',   'label' => 'Salvation Date',   'type' => 'date'],
            ['col' => 'decision_type',    'label' => 'Decision Type',    'type' => 'select', 'options' => ['salvation','rededication','baptism','membership']],
            ['col' => 'follow_up_status', 'label' => 'Follow-up Status', 'type' => 'select', 'options' => ['pending','in_progress','completed','transferred']],
            ['col' => 'notes',            'label' => 'Notes',            'type' => 'textarea'],
        ],
        'visitors' => [
            ['col' => 'full_name',         'label' => 'Full Name',         'type' => 'text', 'required' => true],
            ['col' => 'phone',             'label' => 'Phone',             'type' => 'tel'],
            ['col' => 'email',             'label' => 'Email',             'type' => 'email'],
            ['col' => 'address',           'label' => 'Address',           'type' => 'textarea'],
            ['col' => 'visited_date',      'label' => 'Visit Date',        'type' => 'date'],
            ['col' => 'visit_type',        'label' => 'Visit Type',        'type' => 'select', 'options' => ['first_time','second_time','regular']],
            ['col' => 'referral',          'label' => 'Referred By',       'type' => 'text'],
            ['col' => 'interested_in',     'label' => 'Interested In',     'type' => 'text'],
            ['col' => 'follow_up_status',  'label' => 'Follow-up Status',  'type' => 'select', 'options' => ['pending','contacted','joined','not_interested']],
            ['col' => 'notes',             'label' => 'Notes',             'type' => 'textarea'],
        ],
        'households' => [
            ['col' => 'name',    'label' => 'Household Name', 'type' => 'text', 'required' => true],
            ['col' => 'address', 'label' => 'Address',        'type' => 'textarea'],
            ['col' => 'county',  'label' => 'County',         'type' => 'text'],
            ['col' => 'phone',   'label' => 'Phone',          'type' => 'tel'],
            ['col' => 'notes',   'label' => 'Notes',          'type' => 'textarea'],
        ],
        'groups' => [
            ['col' => 'group_name',   'label' => 'Group Name',    'type' => 'text', 'required' => true],
            ['col' => 'group_kind',   'label' => 'Group Type',    'type' => 'select', 'options' => ['cell','fellowship','choir','youth','women','men','prayer','ministry','committee','other']],
            ['col' => 'meeting_day',  'label' => 'Meeting Day',   'type' => 'select', 'options' => ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']],
            ['col' => 'meeting_time', 'label' => 'Meeting Time',  'type' => 'time'],
            ['col' => 'meeting_venue','label' => 'Meeting Venue', 'type' => 'text'],
            ['col' => 'region',       'label' => 'Region/Zone',   'type' => 'text'],
            ['col' => 'target_size',  'label' => 'Target Size',   'type' => 'number'],
            ['col' => 'notes',        'label' => 'Notes',         'type' => 'textarea'],
        ],
        'partners' => [
            ['col' => 'full_name',    'label' => 'Full Name',    'type' => 'text', 'required' => true],
            ['col' => 'phone',        'label' => 'Phone',        'type' => 'tel'],
            ['col' => 'email',        'label' => 'Email',        'type' => 'email'],
            ['col' => 'address',      'label' => 'Address',      'type' => 'textarea'],
            ['col' => 'partner_since','label' => 'Partner Since','type' => 'date'],
            ['col' => 'tier',         'label' => 'Tier',         'type' => 'select', 'options' => ['bronze','silver','gold','platinum']],
            ['col' => 'intent',       'label' => 'Intent',       'type' => 'select', 'options' => ['tithe','missions','building','general','other']],
            ['col' => 'notes',        'label' => 'Notes',        'type' => 'textarea'],
        ],
        'missions' => [
            ['col' => 'name',            'label' => 'Mission Name',      'type' => 'text', 'required' => true],
            ['col' => 'country',         'label' => 'Country',           'type' => 'text'],
            ['col' => 'region',          'label' => 'Region',            'type' => 'text'],
            ['col' => 'lead_missionary', 'label' => 'Lead Missionary',   'type' => 'text'],
            ['col' => 'start_date',      'label' => 'Start Date',        'type' => 'date'],
            ['col' => 'stage',           'label' => 'Stage',             'type' => 'select', 'options' => ['planning','active','completed','suspended']],
            ['col' => 'budget',          'label' => 'Budget (KES)',       'type' => 'number'],
            ['col' => 'clearance_level', 'label' => 'Clearance Level',   'type' => 'select', 'options' => ['open','restricted','confidential']],
            ['col' => 'description',     'label' => 'Description',       'type' => 'textarea'],
        ],
        'diaspora' => [
            ['col' => 'full_name',        'label' => 'Full Name',        'type' => 'text', 'required' => true],
            ['col' => 'phone',            'label' => 'Phone',            'type' => 'tel'],
            ['col' => 'email',            'label' => 'Email',            'type' => 'email'],
            ['col' => 'country',          'label' => 'Country',          'type' => 'text'],
            ['col' => 'city',             'label' => 'City',             'type' => 'text'],
            ['col' => 'home_county',      'label' => 'Home County (KE)', 'type' => 'text'],
            ['col' => 'church_connection','label' => 'Church Connection','type' => 'text'],
            ['col' => 'notes',            'label' => 'Notes',            'type' => 'textarea'],
        ],
        'business_forum' => [
            ['col' => 'full_name',       'label' => 'Full Name',        'type' => 'text', 'required' => true],
            ['col' => 'business_name',   'label' => 'Business Name',    'type' => 'text'],
            ['col' => 'phone',           'label' => 'Phone',            'type' => 'tel'],
            ['col' => 'email',           'label' => 'Email',            'type' => 'email'],
            ['col' => 'address',         'label' => 'Address',          'type' => 'textarea'],
            ['col' => 'sector',          'label' => 'Business Sector',  'type' => 'text'],
            ['col' => 'membership_level','label' => 'Membership Level', 'type' => 'select', 'options' => ['standard','premium','executive','patron']],
            ['col' => 'notes',           'label' => 'Notes',            'type' => 'textarea'],
        ],
        'church_assets' => [
            ['col' => 'asset_name',        'label' => 'Asset Name',        'type' => 'text', 'required' => true],
            ['col' => 'category',          'label' => 'Category',          'type' => 'select', 'options' => ['land','building','vehicle','equipment','furniture','electronics','other']],
            ['col' => 'description',       'label' => 'Description',       'type' => 'textarea'],
            ['col' => 'serial_no',         'label' => 'Serial / Reg No.',  'type' => 'text'],
            ['col' => 'condition_status',  'label' => 'Condition',         'type' => 'select', 'options' => ['excellent','good','fair','poor','disposed']],
            ['col' => 'acquisition_date',  'label' => 'Acquisition Date',  'type' => 'date'],
            ['col' => 'acquisition_cost',  'label' => 'Acquisition Cost',  'type' => 'number'],
            ['col' => 'current_value',     'label' => 'Current Value',     'type' => 'number'],
            ['col' => 'location',          'label' => 'Location',          'type' => 'text'],
            ['col' => 'responsible_person','label' => 'Responsible Person','type' => 'text'],
            ['col' => 'notes',             'label' => 'Notes',             'type' => 'textarea'],
        ],
    ];

    public static function area(string $area): ?array
    {
        return self::AREAS[$area] ?? null;
    }

    public static function fields(string $area): array
    {
        return self::FIELDS[$area] ?? [];
    }

    public static function displayName(string $area): string
    {
        return self::AREAS[$area]['label'] ?? ucfirst(str_replace('_', ' ', $area));
    }

    public static function primaryLabel(string $area): string
    {
        $fields = self::FIELDS[$area] ?? [];
        foreach ($fields as $f) {
            if (!empty($f['required'])) {
                return $f['col'];
            }
        }
        return 'id';
    }
}
