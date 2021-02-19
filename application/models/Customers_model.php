<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Customers Model
 *
 * MCY - added
 * Handles the db actions that have to do with customers.
 *
 * Data Structure:
 *
 *  'first_name'
 *  'last_name'
 *  'email'
 *  'mobile_number'
 *  'phone_number'
 *  'address'
 *  'city'
 *  'state'
 *  'zip_code'
 *  'notes'
 *  'id_roles'
 *  'providers' >> array with location ids where the pilot volunteers
 *  'settings' >> array with the pilot settings
 * MCY - end of added
 *
 * @package Models
 */
class Customers_model extends EA_Model {
    /**
     * Customers_Model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('data_validation');
        
        // MCY - added
        $this->load->model('user_model');
        // MCY - end of added
    }

    /**
     * Add a customer record to the database.
     *
     * This method adds a customer to the database. If the customer doesn't exists it is going to be inserted, otherwise
     * the record is going to be updated.
     *
     * @param array $customer Associative array with the customer's data. Each key has the same name with the database
     * fields.
     *
     * @return int Returns the customer id.
     * @throws Exception
     */
    public function add($customer)
    {
        // Validate the customer data before doing anything.
        $this->validate($customer);

        // Check if a customer already exists (by email).
        if ($this->exists($customer) && ! isset($customer['id']))
        {
            // Find the customer id from the database.
            $customer['id'] = $this->find_record_id($customer);
        }

        // Insert or update the customer record.
        if ( ! isset($customer['id']))
        {
            $customer['id'] = $this->insert($customer);
        }
        else
        {
            $this->update($customer);
        }

        return $customer['id'];
    }

    /**
     * Validate customer data before the insert or update operation is executed.
     *
     * @param array $customer Contains the customer data.
     *
     * @return bool Returns the validation result.
     *
     * @throws Exception If customer validation fails.
     */
    public function validate($customer)
    {
        // If a customer id is provided, check whether the record exist in the database.
        if (isset($customer['id']))
        {
            $num_rows = $this->db->get_where('users', ['id' => $customer['id']])->num_rows();

            if ($num_rows === 0)
            {
                throw new Exception('Provided customer id does not '
                    . 'exist in the database.');
            }
        }

        $phone_number_required = $this->db->get_where('settings', ['name' => 'require_phone_number'])->row()->value === '1';
        // MCY - added
        // Validate 'providers' value data type (must be array)
        if (isset($customer['providers']) && ! is_array($customer['providers']))
        {
            throw new Exception('Customer providers value is not an array.');
        }
        // MCY - end of added

        // Validate required fields
        if ( ! isset(
                $customer['first_name'],
                $customer['last_name'],
                $customer['email']
            )
            || ( ! isset($customer['phone_number']) && $phone_number_required))
        {
            throw new Exception('Not all required fields are provided: ' . print_r($customer, TRUE));
        }

        // Validate email address
        if ( ! filter_var($customer['email'], FILTER_VALIDATE_EMAIL))
        {
            throw new Exception('Invalid email address provided: ' . $customer['email']);
        }
    
        // MCY - added
        // Check if username exists.
        if (isset($customer['settings']['username']))
        {
            if ( ! $this->user_model->validate_username($customer['settings']['username'], $customer['id']))
            {
                throw new Exception ('Username already exists. Please select a different '
                    . 'username for this record.');
            }
        }

        // Validate customer password.
        if (isset($customer['settings']['password']))
        {
            if (strlen($customer['settings']['password']) < MIN_PASSWORD_LENGTH)
            {
                throw new Exception('The user password must be at least '
                    . MIN_PASSWORD_LENGTH . ' characters long.');
            }
        }

        // Validate calendar view mode. 
        if (isset($customer['settings']['calendar_view']) && ($customer['settings']['calendar_view'] !== CALENDAR_VIEW_DEFAULT
                && $customer['settings']['calendar_view'] !== CALENDAR_VIEW_TABLE))
        {
            throw new Exception('The calendar view setting must be either "' . CALENDAR_VIEW_DEFAULT
                . '" or "' . CALENDAR_VIEW_TABLE . '", given: ' . $customer['settings']['calendar_view']);
        }
        // MCY - end of added

        /** MCY - removed
        // When inserting a record the email address must be unique.
        $customer_id = isset($customer['id']) ? $customer['id'] : '';

        $num_rows = $this->db
            ->select('*')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('roles.slug', DB_SLUG_CUSTOMER)
            ->where('users.email', $customer['email'])
            ->where('users.id !=', $customer_id)
            ->get()
            ->num_rows();
        MCY - end of removed */

        // MCY - changed
        //if ($num_rows > 0)
        if ( ! $this->user_model->validate_email($customer['email'], $customer['id']))
        {
            //throw new Exception('Given email address belongs to another customer record. '
            //    . 'Please use a different email.');
            throw new Exception('Given email address belongs to another user. '
                . 'Please use a different email.');
        }
        // MCY - end of changed

        return TRUE;
    }

    /**
     * Check if a particular customer record already exists.
     *
     * This method checks whether the given customer already exists in the database. It doesn't search with the id, but
     * with the following fields: "email"
     *
     * @param array $customer Associative array with the customer's data. Each key has the same name with the database
     * fields.
     *
     * @return bool Returns whether the record exists or not.
     *
     * @throws Exception If customer email property is missing.
     */
    public function exists($customer)
    {
        if (empty($customer['email']))
        {
            throw new Exception('Customer\'s email is not provided.');
        }

        // This method shouldn't depend on another method of this class.
        $num_rows = $this->db
            ->select('*')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.email', $customer['email'])
            ->where('roles.slug', DB_SLUG_CUSTOMER)
            ->get()->num_rows();

        return $num_rows > 0;
    }

    /**
     * Find the database id of a customer record.
     *
     * The customer data should include the following fields in order to get the unique id from the database: "email"
     *
     * IMPORTANT: The record must already exists in the database, otherwise an exception is raised.
     *
     * @param array $customer Array with the customer data. The keys of the array should have the same names as the
     * database fields.
     *
     * @return int Returns the ID.
     *
     * @throws Exception If customer record does not exist.
     */
    public function find_record_id($customer)
    {
        if (empty($customer['email']))
        {
            throw new Exception('Customer\'s email was not provided: '
                . print_r($customer, TRUE));
        }

        // Get customer's role id
        $result = $this->db
            ->select('users.id')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('users.email', $customer['email'])
            ->where('roles.slug', DB_SLUG_CUSTOMER)
            ->get();

        if ($result->num_rows() == 0)
        {
            throw new Exception('Could not find customer record id.');
        }

        return $result->row()->id;
    }

    /**
     * Insert a new customer record to the database.
     *
     * @param array $customer Associative array with the customer's data. Each key has the same name with the database
     * fields.
     *
     * @return int Returns the id of the new record.
     *
     * @throws Exception If customer record could not be inserted.
     */
    protected function insert($customer)
    {
        // MCY - added
        $this->load->helper('general');
		
        $providers = $customer['providers'];
        unset($customer['providers']);
        $settings = $customer['settings'];
        unset($customer['settings']);
        // MCY - end of added

        // Before inserting the customer we need to get the customer's role id
        // from the database and assign it to the new record as a foreign key.
        $customer_role_id = $this->db
            ->select('id')
            ->from('roles')
            ->where('slug', DB_SLUG_CUSTOMER)
            ->get()->row()->id;

        $customer['id_roles'] = $customer_role_id;

        if ( ! $this->db->insert('users', $customer))
        {
            throw new Exception('Could not insert customer to the database.');
        }
        // MCY - changed
        //return (int)$this->db->insert_id();

        $customer['id'] = (int)$this->db->insert_id();
        $settings['salt'] = generate_salt();
        $settings['password'] = hash_password($settings['salt'], $settings['password']);

        $this->save_providers($providers, $customer['id']);
        $this->save_settings($settings, $customer['id']);

        return $customer['id'];
        // MCY - end of changed
    }

    /**
     * Update an existing customer record in the database.
     *
     * The customer data argument should already include the record ID in order to process the update operation.
     *
     * @param array $customer Associative array with the customer's data. Each key has the same name with the database
     * fields.
     *
     * @return int Returns the updated record ID.
     *
     * @throws Exception If customer record could not be updated.
     */
    protected function update($customer)
    {
        // MCY - added
        $this->load->helper('general');

        $providers = $customer['providers'];
        unset($customer['providers']);
        $settings = $customer['settings'];
        unset($customer['settings']);

        if (isset($settings['password']))
        {
            $salt = $this->db->get_where('user_settings', ['id_users' => $customer['id']])->row()->salt;
            $settings['password'] = hash_password($salt, $settings['password']);
        }
        // MCY - end of added

        $this->db->where('id', $customer['id']);

        if ( ! $this->db->update('users', $customer))
        {
            throw new Exception('Could not update customer to the database.');
        }
    
        // MCY - added
        $this->save_providers($providers, $customer['id']);
	$this->save_settings($settings, $customer['id']);
        // MCY - end of added

        return (int)$customer['id'];
    }

    /**
     * Delete an existing customer record from the database.
     *
     * @param int $customer_id The record id to be deleted.
     *
     * @return bool Returns the delete operation result.
     *
     * @throws Exception If $customer_id argument is invalid.
     */
    public function delete($customer_id)
    {
        if ( ! is_numeric($customer_id))
        {
            throw new Exception('Invalid argument type $customer_id: ' . $customer_id);
        }

        $num_rows = $this->db->get_where('users', ['id' => $customer_id])->num_rows();
        if ($num_rows == 0)
        {
            return FALSE;
        }

        return $this->db->delete('users', ['id' => $customer_id]);
    }

    /**
     * Get a specific row from the appointments table.
     *
     * @param int $customer_id The record's id to be returned.
     *
     * @return array Returns an associative array with the selected record's data. Each key has the same name as the
     * database field names.
     *
     * @throws Exception If $customer_id argumnet is invalid.
     */
    public function get_row($customer_id)
    {
        if ( ! is_numeric($customer_id))
        {
            throw new Exception('Invalid argument provided as $customer_id : ' . $customer_id);
        }
    
        // MCY - changed
        //return $this->db->get_where('users', ['id' => $customer_id])->row_array();

        $customer = $this->db->get_where('users', ['id' => $customer_id])->row_array();

        $customer_providers = $this->db->get_where('secretaries_providers',
            ['id_users_secretary' => $customer['id']])->result_array();
        $customer['providers'] = [];
        foreach ($customer_providers as $customer_provider)
        {
            $customer['providers'][] = $customer_provider['id_users_provider'];
        }

        $customer['settings'] = $this->db->get_where('user_settings',
            ['id_users' => $customer['id']])->row_array();
        unset($customer['settings']['id_users'], $customer['settings']['salt']);

        return $customer;
        // MCY - end of changed
    }

    /**
     * Get a specific field value from the database.
     *
     * @param string $field_name The field name of the value to be returned.
     * @param int $customer_id The selected record's id.
     *
     * @return string Returns the records value from the database.
     *
     * @throws Exception If $customer_id argument is invalid.
     * @throws Exception If $field_name argument is invalid.
     * @throws Exception If requested customer record does not exist in the database.
     * @throws Exception If requested field name does not exist in the database.
     */
    public function get_value($field_name, $customer_id)
    {
        if ( ! is_numeric($customer_id))
        {
            throw new Exception('Invalid argument provided as $customer_id: '
                . $customer_id);
        }

        if ( ! is_string($field_name))
        {
            throw new Exception('$field_name argument is not a string: '
                . $field_name);
        }

        if ($this->db->get_where('users', ['id' => $customer_id])->num_rows() == 0)
        {
            throw new Exception('The record with the $customer_id argument '
                . 'does not exist in the database: ' . $customer_id);
        }

        $row_data = $this->db->get_where('users', ['id' => $customer_id])->row_array();

        if ( ! array_key_exists($field_name, $row_data))
        {
            throw new Exception('The given $field_name argument does not exist in the database: '
                . $field_name);
        }

        $customer = $this->db->get_where('users', ['id' => $customer_id])->row_array();

        return $customer[$field_name];
    }

    /**
     * Get all, or specific records from appointment's table.
     *
     * Example:
     *
     * $this->appointments_model->get_batch([$id => $record_id]);
     *
     * @param mixed|null $where
     * @param int|null $limit
     * @param int|null $offset
     * @param mixed|null $order_by
     *
     * @return array Returns the rows from the database.
     */
    public function get_batch($where = NULL, $limit = NULL, $offset = NULL, $order_by = NULL)
    {
        $role_id = $this->get_customers_role_id();

        if ($where !== NULL)
        {
            $this->db->where($where);
        }

        if ($order_by !== NULL)
        {
            $this->db->order_by($order_by);
        }
    	// MCY - added
    	else
    	{
            $this->db->order_by('users.first_name');
    	}
    	// MCY - end of added
		
        $this->db->where('id_roles', $role_id);

        // MCY - changed
        //return $this->db->get_where('users', ['id_roles' => $role_id], $limit, $offset)->result_array();

        $batch = $this->db->get('users')->result_array();

        // Include every customer settings.
        foreach ($batch as &$customer)
        {
            $customer_providers = $this->db->get_where('secretaries_providers',
                ['id_users_secretary' => $customer['id']])->result_array();

            $customer['providers'] = [];
            foreach ($customer_providers as $customer_provider)
            {
                $customer['providers'][] = $customer_provider['id_users_provider'];
            }

            $customer['settings'] = $this->db->get_where('user_settings',
                ['id_users' => $customer['id']])->row_array();
            unset($customer['settings']['id_users']);
        }

        return $batch;
        // MCY - end of changed
    }

    /**
     * Get the customers role id from the database.
     *
     * @return int Returns the role id for the customer records.
     */
    public function get_customers_role_id()
    {
        return $this->db->get_where('roles', ['slug' => DB_SLUG_CUSTOMER])->row()->id;
    }
	
    // MCY - added
    /**
     * Save a the locations where the pilot can volunteer.
     *
     * @param array $providers Contains the location ids where the pilot can volunteer.
     * @param int $customer_id The selected secretary record.
     *
     * @throws Exception If $providers argument is invalid.
     */
    protected function save_providers($providers, $customer_id)
    {
        if ( ! is_array($providers))
        {
            throw new Exception('Invalid argument given $providers: ' . print_r($providers, TRUE));
        }

        // Delete old connections
        $this->db->delete('secretaries_providers', ['id_users_secretary' => $customer_id]);

        if (count($providers) > 0)
        {
            foreach ($providers as $provider_id)
            {
                $this->db->insert('secretaries_providers', [
                    'id_users_secretary' => $customer_id,
                    'id_users_provider' => $provider_id
                ]);
            }
        }
    }

    /**
     * Save the customer settings (used from insert or update operation).
     *
     * @param array $settings Contains the setting values.
     * @param int $customer_id Record id of the customer.
     *
     * @throws Exception If $customer_id argument is invalid.
     * @throws Exception If $settings argument is invalid.
     */
    protected function save_settings($settings, $customer_id)
    {
        if ( ! is_numeric($customer_id))
        {
            throw new Exception('Invalid $customer_id argument given:' . $customer_id);
        }

        if (count($settings) == 0 || ! is_array($settings))
        {
            throw new Exception('Invalid $settings argument given:' . print_r($settings, TRUE));
        }

        // Check if the setting record exists in db.
        $num_rows = $this->db->get_where('user_settings',
            ['id_users' => $customer_id])->num_rows();
        if ($num_rows == 0)
        {
            $this->db->insert('user_settings', ['id_users' => $customer_id]);
        }

        foreach ($settings as $name => $value)
        {
            $this->set_setting($name, $value, $customer_id);
        }
    }

    /**
     * Get a customer's setting from the database.
     *
     * @param string $setting_name The setting name that is going to be returned.
     * @param int $customer_id The selected customer id.
     *
     * @return string Returns the value of the selected user setting.
     */
    public function get_setting($setting_name, $customer_id)
    {
        $customer_settings = $this->db->get_where('user_settings',
            ['id_users' => $customer_id])->row_array();
        return $customer_settings[$setting_name];
    }

    /**
     * Set a customer's setting value in the database.
     *
     * The customer and settings record must already exist.
     *
     * @param string $setting_name The setting's name.
     * @param string $value The setting's value.
     * @param int $customer_id The selected provider id.
     */
    public function set_setting($setting_name, $value, $customer_id)
    {
        $this->db->where(['id_users' => $customer_id]);
        return $this->db->update('user_settings', [$setting_name => $value]);
    }
    // MCY - end of added
}
