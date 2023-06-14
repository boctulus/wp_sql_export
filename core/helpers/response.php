<?php

// function response($data, $message = '',  int $http_code = 200){
//     if (!is_array($data) || !isset($data['data'])){
//         $res_arr = [
//             'code'    => $http_code,
//             'message' => $message,
//             'data'    => $data
//         ];
//     } else {
//         $res_arr = $data;
//     }

//     $res = new \WP_REST_Response($res_arr);
//     $res->set_status($http_code);

//     return $res;
// }

// /*
//     Is even good idea to use WP_ERROR ?
// */
// function error($error_msg, int $error_code = 500){
//     $error = new \WP_Error();

//     if (is_array($error_msg)){
//         foreach($error_msg as $e_msg){
//             $error->add($error_code, $e_msg);
//         }
//     } else {
//         $error->add($error_code, $error_msg);
//     }

//     return $error;
// }