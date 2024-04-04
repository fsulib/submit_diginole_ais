<?php

namespace Drupal\submit_diginole_ais\Utility;

class SubmitDiginoleSubmissionHelper {

  /**
   * Get license label
   *
   * @param string $license_key
   *
   * @return string
   *    license label
   */
  public static function getLicenseLabel(string $license_key) {
    $label_array = [
      "cc_0" => "Creative Commons Public Domain Dedication (CC0)",
      "cc_by_4.0" => "Creative Commons Attribution (CC BY 4.0)",
      "cc_by_sa_4.0" => "Creative Commons Attribution-ShareAlike (CC BY-SA 4.0)",
      "cc_by_nc_4.0" => "Creative Commons Attribution-NonCommercial (CC BY-NC 4.0)",
      "cc_by_nd_4.0" => "Creative Commons Attribution-NoDerivatives (CC BY-ND 4.0)",
      "cc_by_nc_sa_4.0" => "Creative Commons Attribution-NonCommercial-ShareAlike (CC BY-NC-SA 4.0)",
      "cc_by_nc_nd_4.0" => "Creative Commons Attribution-NonCommercial-NoDerivatives (CC BY-NC-ND 4.0)",
    ];

    return $label_array[$license_key];
  }

  /**
   * Get license link
   *
   * @param string $license_key
   *
   * @return string
   *    license URL
   */
  public static function getLicenseUrl(string $license_key) {
    $label_array = [
      "cc_0" => "https://creativecommons.org/public-domain/cc0/",
      "cc_by_4.0" => "https://creativecommons.org/licenses/by/4.0/",
      "cc_by_sa_4.0" => "https://creativecommons.org/licenses/by-sa/4.0/",
      "cc_by_nc_4.0" => "https://creativecommons.org/licenses/by-nc/4.0/",
      "cc_by_nd_4.0" => "https://creativecommons.org/licenses/by-nd/4.0/",
      "cc_by_nc_sa_4.0" => "https://creativecommons.org/licenses/by-nc-sa/4.0/",
      "cc_by_nc_nd_4.0" => "https://creativecommons.org/licenses/by-nc-nd/4.0/",
    ];

    return $label_array[$license_key];
  }
}
