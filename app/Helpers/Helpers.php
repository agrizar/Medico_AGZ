<?php

namespace App\Helpers;

use Config;
use Illuminate\Support\Facades\Http;
# uses para generar excel
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\DB;

class Helpers
{
  public static function appClasses()
  {

    $data = config('custom.custom');


    // default data array
    $DefaultData = [
      'myLayout' => 'vertical',
      'myTheme' => 'theme-default',
      'myStyle' => 'light',
      'myRTLSupport' => true,
      'myRTLMode' => true,
      'hasCustomizer' => true,
      'showDropdownOnHover' => true,
      'displayCustomizer' => true,
      'menuFixed' => true,
      'menuCollapsed' => false,
      'navbarFixed' => true,
      'footerFixed' => false,
      'menuFlipped' => false,
      // 'menuOffcanvas' => false,
      'customizerControls' => [
        'rtl',
        'style',
        'layoutType',
        'showDropdownOnHover',
        'layoutNavbarFixed',
        'layoutFooterFixed',
        'themes',
      ],
      //   'defaultLanguage'=>'en',
    ];

    // if any key missing of array from custom.php file it will be merge and set a default value from dataDefault array and store in data variable
    $data = array_merge($DefaultData, $data);

    // All options available in the template
    $allOptions = [
      'myLayout' => ['vertical', 'horizontal', 'blank'],
      'menuCollapsed' => [true, false],
      'hasCustomizer' => [true, false],
      'showDropdownOnHover' => [true, false],
      'displayCustomizer' => [true, false],
      'myStyle' => ['light', 'dark'],
      'myTheme' => ['theme-default', 'theme-bordered', 'theme-semi-dark'],
      'myRTLSupport' => [true, false],
      'myRTLMode' => [true, false],
      'menuFixed' => [true, false],
      'navbarFixed' => [true, false],
      'footerFixed' => [true, false],
      'menuFlipped' => [true, false],
      // 'menuOffcanvas' => [true, false],
      'customizerControls' => [],
      // 'defaultLanguage'=>array('en'=>'en','fr'=>'fr','de'=>'de','pt'=>'pt'),
    ];

    //if myLayout value empty or not match with default options in custom.php config file then set a default value
    foreach ($allOptions as $key => $value) {
      if (array_key_exists($key, $DefaultData)) {
        if (gettype($DefaultData[$key]) === gettype($data[$key])) {
          // data key should be string
          if (is_string($data[$key])) {
            // data key should not be empty
            if (isset($data[$key]) && $data[$key] !== null) {
              // data key should not be exist inside allOptions array's sub array
              if (!array_key_exists($data[$key], $value)) {
                // ensure that passed value should be match with any of allOptions array value
                $result = array_search($data[$key], $value, 'strict');
                if (empty($result) && $result !== 0) {
                  $data[$key] = $DefaultData[$key];
                }
              }
            } else {
              // if data key not set or
              $data[$key] = $DefaultData[$key];
            }
          }
        } else {
          $data[$key] = $DefaultData[$key];
        }
      }
    }
    //layout classes
    $layoutClasses = [
      'layout' => $data['myLayout'],
      'theme' => $data['myTheme'],
      'style' => $data['myStyle'],
      'rtlSupport' => $data['myRTLSupport'],
      'rtlMode' => $data['myRTLMode'],
      'textDirection' => $data['myRTLMode'],
      'menuCollapsed' => $data['menuCollapsed'],
      'hasCustomizer' => $data['hasCustomizer'],
      'showDropdownOnHover' => $data['showDropdownOnHover'],
      'displayCustomizer' => $data['displayCustomizer'],
      'menuFixed' => $data['menuFixed'],
      'navbarFixed' => $data['navbarFixed'],
      'footerFixed' => $data['footerFixed'],
      'menuFlipped' => $data['menuFlipped'],
      // 'menuOffcanvas' => $data['menuOffcanvas'],
      'customizerControls' => $data['customizerControls'],
    ];

    // sidebar Collapsed
    if ($layoutClasses['menuCollapsed'] == true) {
      $layoutClasses['menuCollapsed'] = 'layout-menu-collapsed';
    }

    // Menu Fixed
    if ($layoutClasses['menuFixed'] == true) {
      $layoutClasses['menuFixed'] = 'layout-menu-fixed';
    }

    // Navbar Fixed
    if ($layoutClasses['navbarFixed'] == true) {
      $layoutClasses['navbarFixed'] = 'layout-navbar-fixed';
    }

    // Footer Fixed
    if ($layoutClasses['footerFixed'] == true) {
      $layoutClasses['footerFixed'] = 'layout-footer-fixed';
    }

    // Menu Flipped
    if ($layoutClasses['menuFlipped'] == true) {
      $layoutClasses['menuFlipped'] = 'layout-menu-flipped';
    }

    // Menu Offcanvas
    // if ($layoutClasses['menuOffcanvas'] == true) {
    //   $layoutClasses['menuOffcanvas'] = 'layout-menu-offcanvas';
    // }

    // RTL Supported template
    if ($layoutClasses['rtlSupport'] == true) {
      $layoutClasses['rtlSupport'] = '/rtl';
    }

    // RTL Layout/Mode
    if ($layoutClasses['rtlMode'] == true) {
      $layoutClasses['rtlMode'] = 'rtl';
      $layoutClasses['textDirection'] = 'rtl';
    } else {
      $layoutClasses['rtlMode'] = 'ltr';
      $layoutClasses['textDirection'] = 'ltr';
    }

    // Show DropdownOnHover for Horizontal Menu
    if ($layoutClasses['showDropdownOnHover'] == true) {
      $layoutClasses['showDropdownOnHover'] = 'true';
    } else {
      $layoutClasses['showDropdownOnHover'] = 'false';
    }

    // To hide/show display customizer UI, not js
    if ($layoutClasses['displayCustomizer'] == true) {
      $layoutClasses['displayCustomizer'] = 'true';
    } else {
      $layoutClasses['displayCustomizer'] = 'false';
    }

    return $layoutClasses;
  }

  public static function updatePageConfig($pageConfigs)
  {
    $demo = 'custom';
    if (isset($pageConfigs)) {
      if (count($pageConfigs) > 0) {
        foreach ($pageConfigs as $config => $val) {
          Config::set('custom.' . $demo . '.' . $config, $val);
        }
      }
    }
  }

  public static function obtenerToken()
  {
    $loginUrl = env('API_GENERA_TOKEN');

    $response = Http::withoutVerifying()->post($loginUrl, [
        'username' => env('API_RHPAY_USER'),
        'password' => env('API_RHPAY_PASS')
    ]);

    if (!$response->successful()) {
        return null;
    }

    $json = $response->json();

    return $json['token'] ?? null;
  }

  /**
   * Genera el array de selects (DB::raw) a partir del mapa de columnas.
   * Devuelve un array listo para ->select().
   */
  public static function buildSelect(array $columnas, array $mapaColumnas): array
  {
      $select = [];
      foreach ($columnas as $col) {
          if (isset($mapaColumnas[$col])) {
              $select[] = DB::raw($mapaColumnas[$col]);
          }
      }
      return $select;
  }
  
  /**
   * Obtiene un token de autenticación para las APIs de Fortia.
   */
  public static function obtenerTokenAPISFortia($forzarNuevo = false)
  {
    static $token = null;

    if ($token !== null && !$forzarNuevo) {
      return $token;
    }

    $data = [
      'Username' => env('API_FORTIA_USER'),
      'Password' => env('API_FORTIA_PASS'),
    ];

    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => rtrim(env('ENDPOINT_APIS_FORTIA'), '/') . '/login/authenticate',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => json_encode($data),
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
      ],
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => 0,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($curl);

    if ($response === false) {
      $error = curl_error($curl);
      curl_close($curl);
      throw new \RuntimeException('Error Curl al autenticar con Fortia: ' . $error);
    }

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $token = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \RuntimeException('La autenticación de Fortia devolvió una respuesta no JSON. HTTP: ' . $httpCode);
    }

    return $token;
  }

  /**
   * Ejecuta una petición GET contra Fortia.
   */
  public static function ejecutarCurlFortia($endpoint, $token)
  {
    $url = rtrim(env('ENDPOINT_APIS_FORTIA'), '/') . '/' . ltrim($endpoint, '/');
    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPGET => true,
      CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$token}",
        'Accept: application/json',
      ],
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $response = curl_exec($curl);

    if ($response === false) {
      $error = curl_error($curl);
      curl_close($curl);
      throw new \RuntimeException('Error de conexión con Fortia: ' . $error);
    }

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return [
      'status' => $httpCode,
      'data' => json_decode($response, true),
    ];
  }

  /**
   * Consume una API de Fortia y renueva el token si expira.
   */
  public static function consumirFortiaAPI($endpoint, $params = [])
  {
    $query = $params ? '?' . http_build_query($params) : '';

    $token = self::obtenerTokenAPISFortia();

    $response = self::ejecutarCurlFortia(
      $endpoint . $query,
      $token
    );

    if ($response['status'] === 401) {
      $token = self::obtenerTokenAPISFortia(true);
      $response = self::ejecutarCurlFortia($endpoint . $query, $token);
    }

    return $response;
  }

  /**
   * Obtiene los datos de un empleado desde Fortia.
   */
  public static function obtenerDatosEmpleadoFortia($params = [])
  {
    return self::consumirFortiaAPI('personnel-management/employee', $params);
  }

}
