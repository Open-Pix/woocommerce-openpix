### 1.6.0 (2021-08-03)

##### New Features

* **bump:**  bump (3b2b815a)
* **clean:**  some cleanup (cc02abb3)
* **robust:**  even more robust (75e10038)
* **safe:**  be safe (5e70e352)

### 1.5.0 (2021-08-03)

##### Chores

* **debug:**  remove debug lines (c99d3041)

##### New Features

* **readme:**  bump to 1.5.0 and update readme (087b0dfc)
* **customer:**
  *  add 55 on phone number (44d636c2)
  *  person type (b953f561)

##### Bug Fixes

* **customer:**  for new order and reorder (79ab6663)

### 1.4.0 (2021-07-12)

##### New Features

* **bump:**  1.4.0 (99bd000a)
* **release:**  improve release process (f208e90c)

##### Bug Fixes

* **comment:**  be more robust on comment at most 140, see entria/feedback-server#24185 (d4cbafea)

#### 1.3.1 (2021-06-30)

##### New Features

* **new:**  v.1.3.0 (9a4903bd)
* **svn-assets:**  add new imgs (71ce3033)
* **webhook:**  return 200 to avoid webhook retry (4c057d74)

##### Bug Fixes

* **pix:**  better handling of detached pix, fix entria/feedback-server#23615 (496af3cb)

### 1.2.0 (2021-05-27)

##### New Features

* **readme:**  fix readme (3370ce3d)
* **webhook:**  safer on webhook handling, fix entria/feedback-server#23116 (fa45d282)
* **comment:**  remove some comments (5d73b8c7)

##### Bug Fixes

* **checks:**  fix checks (01c76320)
* **version:**
  *  fix version (533470ba)
  *  fix version number on php (c5fd02a3)

### 1.1.0 (2021-05-27)

##### New Features

* **release:**  release automation (745adb4b)
* **i18n:**
  *  translate plugin (010bb00c)
  *  i18n (de779d00)
  *  add translation to OpenPix plugin (20ae838f)
* **status:**  let user customize order status, make wc-pending default, fix entria/feedback-server#23022 (b035bd5c)
* **safe:**
  *  safe cents on wp, fix entria/feedback-server#23006 (fadaeefe)
  *  safe js (2c0fc50d)
* **v1:**
  *  1.0.1 (6462a732)
  *  v1 (c0ae3471)
* **responsive:**  fix wwocommerce responsive fix entria/feedback-server#22703 (9cb10c54)
* **global:**  avoid global functions, prefer static functions instead (431c2862)
* **pack:**  remove backup po pot files (ecbd0f79)
* **sanitize:**  sanitize text fields and email, fix entria/feedback-server#21955 (a3828e9a)
* **translate:**  add mising i18n (13a58394)
* **ipn:**
  *  show proper ipn to be registered at OpenPix (eb6cbbeb)
  *  add ipn handler for webhook (97f72134)
* **check:**  check if respones code is 200, and set production (14e25f1b)
* **cleanup:**  final adjusts (c32996bc)
* **instructions:**  improve instructoins (bb002f62)
* **extract:**  extract css to own file (936dd790)
* **class:**  use class name instead of directly style (1c9610f7)
* **js:**  move js to own file (09d23feb)
* **order:**  vanilla order (037854d8)
* **thank:**  add thank you page to show qrcode image and brcode (f08f2673)
* **plugin:**  use api to generate charge on process payment (d1121f46)
* **vendor:**  do not commit vendor (ca14e1f7)
* **process:**  implement php process_payment with correlation id (bae14bc7)
* **customer:**  handle customer and improve phone number handling (e7cfbd0a)
* **env:**  more structure (1ea52b9e)
* **value:**  get value and description (f27d5637)
* **appid:**  get app id from user defined value (5ef0ac75)
* **infra:**  husky, lint, env, webpack, serve (3e140391)
* **hijack:**  hijack place order button click (de59d520)
* **init:**  ༼ つ ◕_◕ ༽つ  WooCommerce OpenPix Plugin (ef782e46)

##### Bug Fixes

* **version:**  fix stable version and tested up wordpress, see entria/feedback-server#21955 (b70670c4)
* **i18n:**  fix i18n (ce007503)
* **defined:**  fix defined env (027da640)

#### 1.0.2

- Robust float to cents logic
- Be able to customize order status after Pix is emitted

#### 1.0.1

- Responsive improvement

#### 1.0.0

* First version