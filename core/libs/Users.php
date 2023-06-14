<?php

/*
    @author  Pablo Bozzolo boctulus@gmail.com
*/

namespace boctulus\SW\core\libs;

class Users
{
    static function isLogged(){
        // return is_user_logged_in();
        return !empty(get_current_user_id());
    }
    
    static function getCurrentUserId(){
        if (!is_user_logged_in()){
            return null;
        }

        $user_id = get_current_user_id();

        if ($user_id == 0){
            return null;
        }

        return $user_id;
    }

    static function getUserById($id){
        if (!is_numeric($id)){
            throw new \InvalidArgumentException("UID no tiene el formato esperado");
        }

        return get_user_by('id', $id);
    }

    static function getUserByEmail($email){
        return get_user_by('email', $email);
    }

    static function getUserIdByEmail($email){
        $u = get_user_by('email', $email);

        if (!empty($u)){
            return $u->ID;
        }
    }

    static function getUserIdByUsername($username){
        $u = get_user_by('login', $username);

        if (!empty($u)){
            return $u->ID;
        }
    }

    static function getAdminEmail(){
        return get_option('admin_email');
    } 

    // set user meta
    static function setMeta($user_id, $meta_key, $value){
        return update_user_meta($user_id, $meta_key, $value);
    }

    /*
        Ej:

        Users::getMeta('api_key', 7)
    */
    static function getMeta($meta_key, $user_id = null){
        return get_the_author_meta($meta_key, $user_id ?? Users::getCurrentUserId());
    }

    // for All Users
    static function getMetaAll($meta_key = null, $user_id = null){
        $select = ($meta_key === null ? ['user_id', 'meta_key', 'meta_value'] :  ['user_id', 'meta_value']);
        
        $metas = table('usermeta')
        ->select($select)
        ->when($meta_key != null, function($q) use ($meta_key){
            $q->where([
                'meta_key' => $meta_key
            ]);
        })
        ->when($user_id != null, function($q) use ($user_id){
            $q->where([
                'user_id' => $user_id
            ]);
        })
        ->get();

        return $metas;
    }

    /*
        Ej:

        Users::getAPIKey(7)
    */
    static function getAPIKey($user_id = null){
        return static::getMeta('api_key', $user_id);
    }

    /*
        for All Users

        Ej de salida:

        Array
        (
            [0] => Array
                (
                    [user_id] => 8
                    [meta_value] => v8gVyfbF334077
                )

            [1] => Array
                (
                    [user_id] => 7
                    [meta_value] => 3JYiRsVv
                )

        )

    */
    static function getAPIKeyAll(){
        return static::getMetaAll('api_key');
    }

    static function getUserIdByMetaKey($meta_value, $meta_key = null){
        /*
            SELECT `user_id` FROM `woo3`.`wp_usermeta`
            WHERE meta_key = 'user_api_key' AND meta_value = 'woo3-010011010101';
        */

        $user_id = table('usermeta')
        ->select(['user_id'])
        ->where([
            'meta_value' => $meta_value
        ])
        ->when($meta_key != null, function($q) use ($meta_key){
            $q->where([
                'meta_key' => $meta_key
            ]);
        })
        ->value('user_id');

        return $user_id;
    }
    
    static function getUserIdByAPIKey($api_key){
        return Users::getUserIdByMetaKey($api_key, 'api_key');
    }

    static function userExistsByEmail($email){
        return !empty( get_user_by( 'email', $email) );
    }

     /*
        Acepta un rol o array de roles o null para todos
    */
    static function getCapabilities($rol = null){
        global $wp_roles;

        if ($rol == null){
            $rol = static::getRoleSlugs();
        } 

        if (is_array($rol)){
            $capabilities = [];

            // roles
            foreach ($rol as $r){
                if (!static::roleExists($r)){
                    throw new \InvalidArgumentException("Rol '$r' no existe");
                }

                $capabilities[$r] = array_keys($wp_roles->roles[$r]['capabilities'] ); // Warning: Undefined array key "Administrator"
            }
        } else {
            if (!static::roleExists($rol)){
                throw new \InvalidArgumentException("Rol '$rol' no existe");
            }

            $capabilities = array_keys($wp_roles->roles[$rol]['capabilities'] );
        }
    
        return $capabilities;
    }

    /*
        https://wordpress.stackexchange.com/a/111788/99153
    */
    static function roleExists($role) {
        if( ! empty( $role ) ) {
            return $GLOBALS['wp_roles']->is_role( $role );
        }

        return false;
    }

    static function createRole(string $role_name, $role_title = null, Array $capabilities = []){
        $role_name = strtolower($role_name);

        if ($role_title === null){
            $role_title = ucfirst($role_name);
        }

        if (empty($capabilities)){
            $capabilities =  array(
                'read'         => true, // true allows this capability
                'edit_posts'   => true,
                'delete_posts' => true, 
            );
        }

        $result = add_role(
            $role_name,
            __( $role_title ),
           $capabilities
        );

        return (null !== $result);
    }

    /**
     * hasRole 
     *
     * function to check if a user has a specific role
     * 
     * @param  string  $role    role to check against 
     * @param  int  $user_id    user id
     * @return boolean
     * 
     */
    static function hasRole($role, $user = null){
        if (!is_user_logged_in()){
            return false;
        }

        if (empty($user)){
            $user = wp_get_current_user();
        } else {
            if (is_numeric($user) ){
                $user = get_user_by('id', $user);
            }
        }
            
        if ( empty( $user ) )
            return false;

        return in_array( $role, (array) $user->roles );
    }

    /*
        Parece ignorar cualquier rol distinto del primero
    */
    static function addRole($role, $user = null){
        if (empty($user)){
            $user = wp_get_current_user();
        } else {
            if (is_numeric($user) ){
                $user = get_user_by('id', $user);
            }
        }   
        
        return $user->add_role( $role );
    }

    static function removeRole($role, $user = null){
        if (empty($user)){
            $user = wp_get_current_user();
        } else {
            if (is_numeric($user) ){
                $user = get_user_by('id', $user);
            }
        }   

        return $user->remove_role( $role );
    }

    /*
        Lista todos los roles posibles
    */
    static function getRoleNames() {
        global $wp_roles;
        
        if ( ! isset( $wp_roles ) )
            $wp_roles = new \WP_Roles();
        
        return $wp_roles->get_names();
    }

     /*
        Retorna algo como:

       Array
        (
            [0] => administrator
            [1] => editor
            [2] => author
            [3] => contributor
            [4] => subscriber
            [5] => customer
            [6] => shop_manager
        )

    */
    static function getRoleSlugs() {
        return array_keys(static::getRoleNames());
    }

    static function getUsersByRole(Array $roles) {
        $query = new \WP_User_Query(
           array(
              'fields' => 'ID',
              'role__in' => $roles, 
              'limit' => -1        
           )
        );

        return $query->get_results();
    }

    /*
        https://usersinsights.com/wordpress-get-current-user-role/
    */

    static function getCurrentUserRoles() {
        if( is_user_logged_in() ) {
            $user  = wp_get_current_user();
            $roles = (array) $user->roles;
        
            return $roles; 
        } else {   
            return [];
        }   
    }

    static function getUserIDList() {
        $query = new \WP_User_Query(
           array(
              'fields' => 'ID',
              'limit' => -1                 
           )
        );

        return $query->get_results();
    }
    
    /*
        Para WooCommerce
    */

    static function getCustomerList() {
        $query = new \WP_User_Query(
           array(
              'fields' => 'ID',
              'role' => 'customer',
              'limit' => -1                 
           )
        );

        return $query->get_results();
    }

    /*
        Si el $user_id es null, se toma del usuario actual
    */
    static function isPayingCustomer($user_id = null){
        return (bool) Users::getMeta('paying_customer', $user_id);
    }

    static function makePayingCustomer($customer_id = null){
        Users::setMeta( $customer_id, 'paying_customer', 1 );
    }

    static function removeAsPayingCustomer($customer_id = null){
        Users::setMeta( $customer_id, 'paying_customer', 0 );
    }
}