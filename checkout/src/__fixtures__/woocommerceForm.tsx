// language=HTML
export const woocommerceForm = /* HTML */ `
  <form
    name="checkout"
    method="post"
    className="checkout woocommerce-checkout"
    action="http://localhost:10014/checkout/"
    enctype="multipart/form-data"
    novalidate="novalidate"
  >
    <div className="col2-set" id="customer_details">
      <div className="col-1">
        <div className="woocommerce-billing-fields">
          <h3>Billing details</h3>
          <div className="woocommerce-billing-fields__field-wrapper">
            <p
              className="form-row form-row-first validate-required"
              id="billing_first_name_field"
              data-priority="10"
            >
              <label htmlFor="billing_first_name" className=""
                >First name&nbsp;
                <abbr className="required" title="required">*</abbr>
              </label>
              <span className="woocommerce-input-wrapper">
                <input
                  type="text"
                  className="input-text "
                  name="billing_first_name"
                  id="billing_first_name"
                  placeholder=""
                  value=""
                  autocomplete="given-name"
                />
              </span>
            </p>
            <p
              className="form-row form-row-last validate-required"
              id="billing_last_name_field"
              data-priority="20"
            >
              <label htmlFor="billing_last_name" className=""
                >Last name&nbsp;
                <abbr className="required" title="required">*</abbr>
              </label>
              <span className="woocommerce-input-wrapper">
                <input
                  type="text"
                  className="input-text "
                  name="billing_last_name"
                  id="billing_last_name"
                  placeholder=""
                  value=""
                  autocomplete="family-name"
                />
              </span>
            </p>
            <p
              className="form-row form-row-wide person-type-field validate-required woocommerce-validated"
              id="billing_persontype_field"
              data-priority="22"
            >
              <label htmlFor="billing_persontype" className=""
                >Person type&nbsp;
                <span className="optional">(optional)</span>
                <abbr className="required" title="required">*</abbr>
              </label>
              <span className="woocommerce-input-wrapper">
                <select
                  name="billing_persontype"
                  id="billing_persontype"
                  className="select wc-ecfb-select select2-hidden-accessible"
                  data-placeholder=""
                  tabindex="-1"
                  aria-hidden="true"
                >
                  <option value="1">Individuals</option>
                  <option value="2">Legal Person</option>
                </select>
                <span
                  className="select2 select2-container select2-container--default"
                  dir="ltr"
                  style="width: 416.328px"
                >
                  <span className="selection">
                    <span
                      className="select2-selection select2-selection--single"
                      aria-haspopup="true"
                      aria-expanded="false"
                      tabIndex="0"
                      aria-labelledby="select2-billing_persontype-container"
                      role="combobox"
                    >
                      <span
                        className="select2-selection__rendered"
                        id="select2-billing_persontype-container"
                        role="textbox"
                        aria-readonly="true"
                        title="Individuals"
                        >Individuals</span
                      >
                      <span
                        className="select2-selection__arrow"
                        role="presentation"
                      >
                        <b role="presentation"></b>
                      </span>
                    </span>
                  </span>
                  <span className="dropdown-wrapper" aria-hidden="true"></span>
                </span>
              </span>
            </p>
            <p
              className="form-row form-row-wide person-type-field validate-required"
              id="billing_cpf_field"
              data-priority="23"
              style=""
            >
              <label htmlFor="billing_cpf" className=""
                >CPF&nbsp;
                <span className="optional">(optional)</span>
                <abbr className="required" title="required">*</abbr>
              </label>
              <span className="woocommerce-input-wrapper">
                <input
                  type="tel"
                  className="input-text "
                  name="billing_cpf"
                  id="billing_cpf"
                  placeholder=""
                  value=""
                  maxlength="14"
                />
              </span>
            </p>
            <p
              className="form-row form-row-wide"
              id="billing_company_field"
              data-priority="25"
              style="display: none"
            >
              <label htmlFor="billing_company" className=""
                >Company name&nbsp;
                <span className="optional">(optional)</span>
              </label>
              <span className="woocommerce-input-wrapper">
                <input
                  type="text"
                  className="input-text "
                  name="billing_company"
                  id="billing_company"
                  placeholder=""
                  value=""
                  autocomplete="organization"
                />
              </span>
            </p>
            <p
              className="form-row form-row-wide person-type-field validate-required"
              id="billing_cnpj_field"
              data-priority="26"
              style="display: none"
            >
              <label htmlFor="billing_cnpj" className=""
                >CNPJ&nbsp;
                <span className="optional">(optional)</span>
                <abbr className="required" title="required">*</abbr>
              </label>
              <span className="woocommerce-input-wrapper">
                <input
                  type="tel"
                  className="input-text "
                  name="billing_cnpj"
                  id="billing_cnpj"
                  placeholder=""
                  value=""
                  maxlength="18"
                />
              </span>
            </p>
            <p
              className="form-row form-row-wide address-field update_totals_on_change validate-required"
              id="billing_country_field"
              data-priority="40"
            >
              <label htmlFor="billing_country" className=""
                >Country / Region&nbsp;
                <abbr className="required" title="required">*</abbr>
              </label>
              <span className="woocommerce-input-wrapper">
                <select
                  name="billing_country"
                  id="billing_country"
                  className="country_to_state country_select select2-hidden-accessible"
                  autocomplete="country"
                  data-placeholder="Select a country / region…"
                  data-label="Country / Region"
                  tabindex="-1"
                  aria-hidden="true"
                >
                  <option value="">Select a country / region…</option>
                  <option value="AF">Afghanistan</option>
                  <option value="AX">Åland Islands</option>
                  <option value="AL">Albania</option>
                  <option value="DZ">Algeria</option>
                  <option value="AS">American Samoa</option>
                  <option value="AD">Andorra</option>
                  <option value="AO">Angola</option>
                  <option value="AI">Anguilla</option>
                  <option value="AQ">Antarctica</option>
                  <option value="AG">Antigua and Barbuda</option>
                  <option value="AR">Argentina</option>
                  <option value="AM">Armenia</option>
                  <option value="AW">Aruba</option>
                  <option value="AU">Australia</option>
                  <option value="AT">Austria</option>
                  <option value="AZ">Azerbaijan</option>
                  <option value="BS">Bahamas</option>
                  <option value="BH">Bahrain</option>
                  <option value="BD">Bangladesh</option>
                  <option value="BB">Barbados</option>
                  <option value="BY">Belarus</option>
                  <option value="PW">Belau</option>
                  <option value="BE">Belgium</option>
                  <option value="BZ">Belize</option>
                  <option value="BJ">Benin</option>
                  <option value="BM">Bermuda</option>
                  <option value="BT">Bhutan</option>
                  <option value="BO">Bolivia</option>
                  <option value="BQ">Bonaire, Saint Eustatius and Saba</option>
                  <option value="BA">Bosnia and Herzegovina</option>
                  <option value="BW">Botswana</option>
                  <option value="BV">Bouvet Island</option>
                  <option value="BR" selected="selected">Brazil</option>
                  <option value="IO">British Indian Ocean Territory</option>
                  <option value="BN">Brunei</option>
                  <option value="BG">Bulgaria</option>
                  <option value="BF">Burkina Faso</option>
                  <option value="BI">Burundi</option>
                  <option value="KH">Cambodia</option>
                  <option value="CM">Cameroon</option>
                  <option value="CA">Canada</option>
                  <option value="CV">Cape Verde</option>
                  <option value="KY">Cayman Islands</option>
                  <option value="CF">Central African Republic</option>
                  <option value="TD">Chad</option>
                  <option value="CL">Chile</option>
                  <option value="CN">China</option>
                  <option value="CX">Christmas Island</option>
                  <option value="CC">Cocos (Keeling) Islands</option>
                  <option value="CO">Colombia</option>
                  <option value="KM">Comoros</option>
                  <option value="CG">Congo (Brazzaville)</option>
                  <option value="CD">Congo (Kinshasa)</option>
                  <option value="CK">Cook Islands</option>
                  <option value="CR">Costa Rica</option>
                  <option value="HR">Croatia</option>
                  <option value="CU">Cuba</option>
                  <option value="CW">Curaçao</option>
                  <option value="CY">Cyprus</option>
                  <option value="CZ">Czech Republic</option>
                  <option value="DK">Denmark</option>
                  <option value="DJ">Djibouti</option>
                  <option value="DM">Dominica</option>
                  <option value="DO">Dominican Republic</option>
                  <option value="EC">Ecuador</option>
                  <option value="EG">Egypt</option>
                  <option value="SV">El Salvador</option>
                  <option value="GQ">Equatorial Guinea</option>
                  <option value="ER">Eritrea</option>
                  <option value="EE">Estonia</option>
                  <option value="ET">Ethiopia</option>
                  <option value="FK">Falkland Islands</option>
                  <option value="FO">Faroe Islands</option>
                  <option value="FJ">Fiji</option>
                  <option value="FI">Finland</option>
                  <option value="FR">France</option>
                  <option value="GF">French Guiana</option>
                  <option value="PF">French Polynesia</option>
                  <option value="TF">French Southern Territories</option>
                  <option value="GA">Gabon</option>
                  <option value="GM">Gambia</option>
                  <option value="GE">Georgia</option>
                  <option value="DE">Germany</option>
                  <option value="GH">Ghana</option>
                  <option value="GI">Gibraltar</option>
                  <option value="GR">Greece</option>
                  <option value="GL">Greenland</option>
                  <option value="GD">Grenada</option>
                  <option value="GP">Guadeloupe</option>
                  <option value="GU">Guam</option>
                  <option value="GT">Guatemala</option>
                  <option value="GG">Guernsey</option>
                  <option value="GN">Guinea</option>
                  <option value="GW">Guinea-Bissau</option>
                  <option value="GY">Guyana</option>
                  <option value="HT">Haiti</option>
                  <option value="HM">Heard Island and McDonald Islands</option>
                  <option value="HN">Honduras</option>
                  <option value="HK">Hong Kong</option>
                  <option value="HU">Hungary</option>
                  <option value="IS">Iceland</option>
                  <option value="IN">India</option>
                  <option value="ID">Indonesia</option>
                  <option value="IR">Iran</option>
                  <option value="IQ">Iraq</option>
                  <option value="IE">Ireland</option>
                  <option value="IM">Isle of Man</option>
                  <option value="IL">Israel</option>
                  <option value="IT">Italy</option>
                  <option value="CI">Ivory Coast</option>
                  <option value="JM">Jamaica</option>
                  <option value="JP">Japan</option>
                  <option value="JE">Jersey</option>
                  <option value="JO">Jordan</option>
                  <option value="KZ">Kazakhstan</option>
                  <option value="KE">Kenya</option>
                  <option value="KI">Kiribati</option>
                  <option value="KW">Kuwait</option>
                  <option value="KG">Kyrgyzstan</option>
                  <option value="LA">Laos</option>
                  <option value="LV">Latvia</option>
                  <option value="LB">Lebanon</option>
                  <option value="LS">Lesotho</option>
                  <option value="LR">Liberia</option>
                  <option value="LY">Libya</option>
                  <option value="LI">Liechtenstein</option>
                  <option value="LT">Lithuania</option>
                  <option value="LU">Luxembourg</option>
                  <option value="MO">Macao</option>
                  <option value="MG">Madagascar</option>
                  <option value="MW">Malawi</option>
                  <option value="MY">Malaysia</option>
                  <option value="MV">Maldives</option>
                  <option value="ML">Mali</option>
                  <option value="MT">Malta</option>
                  <option value="MH">Marshall Islands</option>
                  <option value="MQ">Martinique</option>
                  <option value="MR">Mauritania</option>
                  <option value="MU">Mauritius</option>
                  <option value="YT">Mayotte</option>
                  <option value="MX">Mexico</option>
                  <option value="FM">Micronesia</option>
                  <option value="MD">Moldova</option>
                  <option value="MC">Monaco</option>
                  <option value="MN">Mongolia</option>
                  <option value="ME">Montenegro</option>
                  <option value="MS">Montserrat</option>
                  <option value="MA">Morocco</option>
                  <option value="MZ">Mozambique</option>
                  <option value="MM">Myanmar</option>
                  <option value="NA">Namibia</option>
                  <option value="NR">Nauru</option>
                  <option value="NP">Nepal</option>
                  <option value="NL">Netherlands</option>
                  <option value="NC">New Caledonia</option>
                  <option value="NZ">New Zealand</option>
                  <option value="NI">Nicaragua</option>
                  <option value="NE">Niger</option>
                  <option value="NG">Nigeria</option>
                  <option value="NU">Niue</option>
                  <option value="NF">Norfolk Island</option>
                  <option value="KP">North Korea</option>
                  <option value="MK">North Macedonia</option>
                  <option value="MP">Northern Mariana Islands</option>
                  <option value="NO">Norway</option>
                  <option value="OM">Oman</option>
                  <option value="PK">Pakistan</option>
                  <option value="PS">Palestinian Territory</option>
                  <option value="PA">Panama</option>
                  <option value="PG">Papua New Guinea</option>
                  <option value="PY">Paraguay</option>
                  <option value="PE">Peru</option>
                  <option value="PH">Philippines</option>
                  <option value="PN">Pitcairn</option>
                  <option value="PL">Poland</option>
                  <option value="PT">Portugal</option>
                  <option value="PR">Puerto Rico</option>
                  <option value="QA">Qatar</option>
                  <option value="RE">Reunion</option>
                  <option value="RO">Romania</option>
                  <option value="RU">Russia</option>
                  <option value="RW">Rwanda</option>
                  <option value="BL">Saint Barthélemy</option>
                  <option value="SH">Saint Helena</option>
                  <option value="KN">Saint Kitts and Nevis</option>
                  <option value="LC">Saint Lucia</option>
                  <option value="SX">Saint Martin (Dutch part)</option>
                  <option value="MF">Saint Martin (French part)</option>
                  <option value="PM">Saint Pierre and Miquelon</option>
                  <option value="VC">Saint Vincent and the Grenadines</option>
                  <option value="WS">Samoa</option>
                  <option value="SM">San Marino</option>
                  <option value="ST">São Tomé and Príncipe</option>
                  <option value="SA">Saudi Arabia</option>
                  <option value="SN">Senegal</option>
                  <option value="RS">Serbia</option>
                  <option value="SC">Seychelles</option>
                  <option value="SL">Sierra Leone</option>
                  <option value="SG">Singapore</option>
                  <option value="SK">Slovakia</option>
                  <option value="SI">Slovenia</option>
                  <option value="SB">Solomon Islands</option>
                  <option value="SO">Somalia</option>
                  <option value="ZA">South Africa</option>
                  <option value="GS">South Georgia/Sandwich Islands</option>
                  <option value="KR">South Korea</option>
                  <option value="SS">South Sudan</option>
                  <option value="ES">Spain</option>
                  <option value="LK">Sri Lanka</option>
                  <option value="SD">Sudan</option>
                  <option value="SR">Suriname</option>
                  <option value="SJ">Svalbard and Jan Mayen</option>
                  <option value="SZ">Swaziland</option>
                  <option value="SE">Sweden</option>
                  <option value="CH">Switzerland</option>
                  <option value="SY">Syria</option>
                  <option value="TW">Taiwan</option>
                  <option value="TJ">Tajikistan</option>
                  <option value="TZ">Tanzania</option>
                  <option value="TH">Thailand</option>
                  <option value="TL">Timor-Leste</option>
                  <option value="TG">Togo</option>
                  <option value="TK">Tokelau</option>
                  <option value="TO">Tonga</option>
                  <option value="TT">Trinidad and Tobago</option>
                  <option value="TN">Tunisia</option>
                  <option value="TR">Turkey</option>
                  <option value="TM">Turkmenistan</option>
                  <option value="TC">Turks and Caicos Islands</option>
                  <option value="TV">Tuvalu</option>
                  <option value="UG">Uganda</option>
                  <option value="UA">Ukraine</option>
                  <option value="AE">United Arab Emirates</option>
                  <option value="GB">United Kingdom (UK)</option>
                  <option value="US">United States (US)</option>
                  <option value="UM">
                    United States (US) Minor Outlying Islands
                  </option>
                  <option value="UY">Uruguay</option>
                  <option value="UZ">Uzbekistan</option>
                  <option value="VU">Vanuatu</option>
                  <option value="VA">Vatican</option>
                  <option value="VE">Venezuela</option>
                  <option value="VN">Vietnam</option>
                  <option value="VG">Virgin Islands (British)</option>
                  <option value="VI">Virgin Islands (US)</option>
                  <option value="WF">Wallis and Futuna</option>
                  <option value="EH">Western Sahara</option>
                  <option value="YE">Yemen</option>
                  <option value="ZM">Zambia</option>
                  <option value="ZW">Zimbabwe</option>
                </select>
                <span
                  className="select2 select2-container select2-container--default"
                  dir="ltr"
                  style="width: 100%"
                >
                  <span className="selection">
                    <span
                      className="select2-selection select2-selection--single"
                      aria-haspopup="true"
                      aria-expanded="false"
                      tabIndex="0"
                      aria-label="Country / Region"
                      role="combobox"
                    >
                      <span
                        className="select2-selection__rendered"
                        id="select2-billing_country-container"
                        role="textbox"
                        aria-readonly="true"
                        title="Brazil"
                        >Brazil</span
                      >
                      <span
                        className="select2-selection__arrow"
                        role="presentation"
                      >
                        <b role="presentation"></b>
                      </span>
                    </span>
                  </span>
                  <span className="dropdown-wrapper" aria-hidden="true"></span>
                </span>
                <noscript>
                  <button
                    type="submit"
                    name="woocommerce_checkout_update_totals"
                    value="Update country / region"
                  >
                    Update country / region
                  </button>
                </noscript>
              </span>
            </p>
            <p
              className="form-row address-field validate-required validate-postcode form-row-wide"
              id="billing_postcode_field"
              data-priority="45"
              data-o_class="form-row form-row-first address-field validate-required validate-postcode"
            >
              <label htmlFor="billing_postcode" className=""
                >Postcode / ZIP&nbsp;
                <abbr className="required" title="required">*</abbr>
              </label>
              <span className="woocommerce-input-wrapper">
                <input
                  type="tel"
                  className="input-text "
                  name="billing_postcode"
                  id="billing_postcode"
                  placeholder=""
                  value=""
                  autocomplete="postal-code"
                  maxlength="9"
                />
              </span>
            </p>
            <p
              className="form-row address-field validate-required form-row-wide"
              id="billing_address_1_field"
              data-priority="50"
            >
              <label htmlFor="billing_address_1" className=""
                >Street address&nbsp;
                <abbr className="required" title="required">*</abbr>
              </label>
              <span className="woocommerce-input-wrapper">
                <input
                  type="text"
                  className="input-text "
                  name="billing_address_1"
                  id="billing_address_1"
                  placeholder="House number and street name"
                  value=""
                  autocomplete="address-line1"
                  data-placeholder="House number and street name"
                />
              </span>
            </p>
            <p
              className="form-row form-row-first address-field validate-required"
              id="billing_number_field"
              data-priority="55"
            >
              <label htmlFor="billing_number" className=""
                >Number&nbsp;
                <abbr className="required" title="required">*</abbr>
              </label>
              <span className="woocommerce-input-wrapper">
                <input
                  type="text"
                  className="input-text "
                  name="billing_number"
                  id="billing_number"
                  placeholder=""
                  value=""
                />
              </span>
            </p>
            <p
              className="form-row address-field form-row-wide"
              id="billing_address_2_field"
              data-priority="60"
            >
              <label htmlFor="billing_address_2" className="screen-reader-text"
                >Apartment, suite, unit, etc.&nbsp;

                <span className="optional">(optional)</span>
              </label>
              <span className="woocommerce-input-wrapper">
                <input
                  type="text"
                  className="input-text "
                  name="billing_address_2"
                  id="billing_address_2"
                  placeholder="Apartment, suite, unit, etc. (optional)"
                  value=""
                  autocomplete="address-line2"
                  data-placeholder="Apartment, suite, unit, etc. (optional)"
                />
              </span>
            </p>
            <p
              className="form-row form-row-first address-field"
              id="billing_neighborhood_field"
              data-priority="65"
            >
              <label htmlFor="billing_neighborhood" className=""
                >Neighborhood&nbsp;
                <span className="optional">(optional)</span>
              </label>
              <span className="woocommerce-input-wrapper">
                <input
                  type="text"
                  className="input-text "
                  name="billing_neighborhood"
                  id="billing_neighborhood"
                  placeholder=""
                  value=""
                />
              </span>
            </p>
            <p
              className="form-row address-field validate-required form-row-wide"
              id="billing_city_field"
              data-priority="70"
              data-o_class="form-row form-row-last address-field validate-required"
            >
              <label htmlFor="billing_city" className=""
                >Town / City&nbsp;
                <abbr className="required" title="required">*</abbr>
              </label>
              <span className="woocommerce-input-wrapper">
                <input
                  type="text"
                  className="input-text "
                  name="billing_city"
                  id="billing_city"
                  placeholder=""
                  value=""
                  autocomplete="address-level2"
                />
              </span>
            </p>
            <p
              className="form-row address-field validate-required validate-state form-row-wide"
              id="billing_state_field"
              data-priority="80"
              data-o_class="form-row form-row-wide address-field validate-required validate-state"
            >
              <label htmlFor="billing_state" className=""
                >State / County&nbsp;
                <abbr className="required" title="required">*</abbr>
              </label>
              <span className="woocommerce-input-wrapper">
                <select
                  name="billing_state"
                  id="billing_state"
                  className="state_select select2-hidden-accessible"
                  autocomplete="address-level1"
                  data-placeholder="Select an option…"
                  data-input-classes=""
                  data-label="State / County"
                  tabindex="-1"
                  aria-hidden="true"
                >
                  <option value="">Select an option…</option>
                  <option value="AC">Acre</option>
                  <option value="AL">Alagoas</option>
                  <option value="AP">Amapá</option>
                  <option value="AM">Amazonas</option>
                  <option value="BA">Bahia</option>
                  <option value="CE">Ceará</option>
                  <option value="DF">Distrito Federal</option>
                  <option value="ES">Espírito Santo</option>
                  <option value="GO">Goiás</option>
                  <option value="MA">Maranhão</option>
                  <option value="MT">Mato Grosso</option>
                  <option value="MS">Mato Grosso do Sul</option>
                  <option value="MG">Minas Gerais</option>
                  <option value="PA">Pará</option>
                  <option value="PB">Paraíba</option>
                  <option value="PR">Paraná</option>
                  <option value="PE">Pernambuco</option>
                  <option value="PI">Piauí</option>
                  <option value="RJ">Rio de Janeiro</option>
                  <option value="RN">Rio Grande do Norte</option>
                  <option value="RS">Rio Grande do Sul</option>
                  <option value="RO">Rondônia</option>
                  <option value="RR">Roraima</option>
                  <option value="SC">Santa Catarina</option>
                  <option value="SP">São Paulo</option>
                  <option value="SE">Sergipe</option>
                  <option value="TO">Tocantins</option>
                </select>
                <span
                  className="select2 select2-container select2-container--default"
                  dir="ltr"
                  style="width: 100%"
                >
                  <span className="selection">
                    <span
                      className="select2-selection select2-selection--single"
                      aria-haspopup="true"
                      aria-expanded="false"
                      tabIndex="0"
                      aria-label="State / County"
                      role="combobox"
                    >
                      <span
                        className="select2-selection__rendered"
                        id="select2-billing_state-container"
                        role="textbox"
                        aria-readonly="true"
                        title="São Paulo"
                        >São Paulo</span
                      >
                      <span
                        className="select2-selection__arrow"
                        role="presentation"
                      >
                        <b role="presentation"></b>
                      </span>
                    </span>
                  </span>
                  <span className="dropdown-wrapper" aria-hidden="true"></span>
                </span>
              </span>
            </p>
            <p
              className="form-row form-row-first validate-required validate-phone"
              id="billing_phone_field"
              data-priority="100"
            >
              <label htmlFor="billing_phone" className=""
                >Phone&nbsp;
                <abbr className="required" title="required">*</abbr>
              </label>
              <span className="woocommerce-input-wrapper">
                <input
                  type="tel"
                  className="input-text "
                  name="billing_phone"
                  id="billing_phone"
                  placeholder=""
                  value=""
                  autocomplete="tel"
                  maxlength="15"
                />
              </span>
            </p>
            <p
              className="form-row form-row-last"
              id="billing_cellphone_field"
              data-priority="105"
            >
              <label htmlFor="billing_cellphone" className=""
                >Cell Phone&nbsp;
                <span className="optional">(optional)</span>
              </label>
              <span className="woocommerce-input-wrapper">
                <input
                  type="tel"
                  className="input-text "
                  name="billing_cellphone"
                  id="billing_cellphone"
                  placeholder=""
                  value=""
                  maxlength="15"
                />
              </span>
            </p>
            <p
              className="form-row form-row-wide validate-required validate-email"
              id="billing_email_field"
              data-priority="110"
            >
              <label htmlFor="billing_email" className=""
                >Email address&nbsp;
                <abbr className="required" title="required">*</abbr>
              </label>
              <span className="woocommerce-input-wrapper">
                <input
                  type="email"
                  className="input-text "
                  name="billing_email"
                  id="billing_email"
                  placeholder=""
                  value=""
                  autocomplete="email username"
                />
                <div
                  id="wcbcf-mailsuggest"
                  style="color: rgb(204, 0, 0); font-size: small"
                ></div>
              </span>
            </p>
          </div>
        </div>
      </div>
      <div className="col-2">
        <div className="woocommerce-shipping-fields"></div>
        <div className="woocommerce-additional-fields">
          <h3>Additional information</h3>
          <div className="woocommerce-additional-fields__field-wrapper">
            <p
              className="form-row notes"
              id="order_comments_field"
              data-priority=""
            >
              <label htmlFor="order_comments" className=""
                >Order notes&nbsp;
                <span className="optional">(optional)</span>
              </label>
              <span className="woocommerce-input-wrapper">
                <textarea
                  name="order_comments"
                  className="input-text "
                  id="order_comments"
                  placeholder="Notes about your order, e.g. special notes for delivery."
                  rows="2"
                  cols="5"
                ></textarea>
              </span>
            </p>
          </div>
        </div>
      </div>
    </div>
    <h3 id="order_review_heading">Your order</h3>
    <div id="order_review" className="woocommerce-checkout-review-order">
      <table className="shop_table woocommerce-checkout-review-order-table">
        <thead>
          <tr>
            <th className="product-name">Product</th>
            <th className="product-total">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <tr className="cart_item">
            <td className="product-name">
              avatar&nbsp;
              <strong className="product-quantity">×&nbsp;1</strong>
            </td>
            <td className="product-total">
              <span className="woocommerce-Price-amount amount">
                <bdi>
                  <span className="woocommerce-Price-currencySymbol">R$</span
                  >15,00
                </bdi>
              </span>
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr className="cart-subtotal">
            <th>Subtotal</th>
            <td>
              <span className="woocommerce-Price-amount amount">
                <bdi>
                  <span className="woocommerce-Price-currencySymbol">R$</span
                  >15,00
                </bdi>
              </span>
            </td>
          </tr>
          <tr className="order-total">
            <th>Total</th>
            <td>
              <strong>
                <span className="woocommerce-Price-amount amount">
                  <bdi>
                    <span className="woocommerce-Price-currencySymbol">R$</span
                    >15,00
                  </bdi>
                </span>
              </strong>
            </td>
          </tr>
        </tfoot>
      </table>
      <div id="payment" className="woocommerce-checkout-payment">
        <ul className="wc_payment_methods payment_methods methods">
          <li
            className="wc_payment_method payment_method_woocommerce_openpix_pix"
          >
            <input
              id="payment_method_woocommerce_openpix_pix"
              type="radio"
              className="input-radio"
              name="payment_method"
              value="woocommerce_openpix_pix"
              checked="checked"
              data-order_button_text="Pay with Pix"
              style="display: none"
            />
            <label htmlFor="payment_method_woocommerce_openpix_pix">
              Pay with Pix
            </label>
            <div className="payment_box payment_method_woocommerce_openpix_pix">
              <p>Pay with Pix</p>
              <div id="openpix-checkout-params" data-total="1500"></div>
            </div>
          </li>
        </ul>
        <div className="form-row place-order">
          <noscript>
            Since your browser does not support JavaScript, or it is disabled,
            please ensure you click the
            <em>Update Totals</em> button before placing your order. You may be
            charged more than the amount stated above if you fail to do so.
            <br />
            <button
              type="submit"
              className="button alt"
              name="woocommerce_checkout_update_totals"
              value="Update totals"
            >
              Update totals
            </button>
          </noscript>
          <div className="woocommerce-terms-and-conditions-wrapper">
            <div className="woocommerce-privacy-policy-text">
              <p>
                Your personal data will be used to process your order, support
                your experience throughout this website, and for other purposes
                described in our
                <a
                  href="http://localhost:10014/?page_id=3"
                  className="woocommerce-privacy-policy-link"
                  target="_blank"
                  >privacy policy</a
                >.
              </p>
            </div>
          </div>
          <button
            type="submit"
            className="button alt"
            name="woocommerce_checkout_place_order"
            id="place_order"
            value="Place order"
            data-value="Place order"
          >
            Pay with Pix
          </button>
          <input
            type="hidden"
            id="woocommerce-process-checkout-nonce"
            name="woocommerce-process-checkout-nonce"
            value="18607d0276"
          />
          <input
            type="hidden"
            name="_wp_http_referer"
            value="/?wc-ajax=update_order_review"
          />
        </div>
      </div>
    </div>
  </form>
`;
