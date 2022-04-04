#### 2.1.1 (2022-04-04)

##### New Features

* **blank-param:**  check os to run sed (#406) (3664bf15)
* **readme:**
  *  readme 2.1.1 (#405) (bb38c731)
  *  readme 2.1.0 (ceeddd20)
* **tests:**  try to add tests (e1466e27)
* **improve:**  improved logs (70d1d379)
* **split-plugins:**
  *  conditional zip (b26522ce)
  *  try to split the prod and staging (ffd15b17)

##### Bug Fixes

* **coupon:**  limit usage to 1 (99a1c8b5)
* **bug:**  do not decode a param, fix entria/woovi#31325 (712c2834)

#### 2.1.0 (2022-04-01)

##### Build System / Dependencies

* **deps-dev:**
  *  bump @testing-library/react from 12.1.4 to 13.0.0 (a8616137)
  *  bump @testing-library/user-event from 14.0.0 to 14.0.3 (#398) (a831d88c)
  *  bump react-refresh from 0.11.0 to 0.12.0 (#393) (d5cc01ea)
  *  bump eslint-plugin-react-hooks from 4.3.0 to 4.4.0 (#388) (caefc100)
  *  bump react-refresh from 0.11.0 to 0.12.0 (#387) (62fd6286)
  *  bump simple-git from 3.4.0 to 3.5.0 (#386) (9d09d891)
  *  bump @testing-library/user-event from 13.5.0 to 14.0.0 (4741cccb)
  *  bump @typescript-eslint/eslint-plugin (#384) (dda28b2f)
  *  bump @typescript-eslint/parser from 5.16.0 to 5.17.0 (#383) (0594d5e9)
  *  bump @testing-library/jest-dom from 5.16.2 to 5.16.3 (#382) (e94086b7)
  *  bump @testing-library/dom from 8.11.4 to 8.12.0 (#380) (75408f29)
  *  bump @types/react from 17.0.42 to 17.0.43 (#381) (a60b614e)
  *  bump @prettier/plugin-php from 0.18.3 to 0.18.4 (#378) (228851fd)
  *  bump eslint from 8.11.0 to 8.12.0 (#376) (7c52a4c1)
  *  bump prettier from 2.6.0 to 2.6.1 (#375) (a5b108eb)
  *  bump typescript from 4.6.2 to 4.6.3 (#374) (1ae495c6)
  *  bump @testing-library/dom from 8.11.3 to 8.11.4 (#370) (9b0fbe85)
  *  bump babel-loader from 8.2.3 to 8.2.4 (#369) (f4057f4f)
  *  bump @types/react from 17.0.41 to 17.0.42 (#368) (2f178ae3)
  *  bump eslint-import-resolver-typescript (#367) (1e29e194)
  *  bump @typescript-eslint/eslint-plugin (#364) (189726f4)
  *  bump @typescript-eslint/parser from 5.15.0 to 5.16.0 (#363) (a238af68)
  *  bump @types/react-dom from 17.0.13 to 17.0.14 (#359) (df81c1fc)
  *  bump @babel/core from 7.17.7 to 7.17.8 (#361) (ad2b6ab2)
  *  bump @types/babel__core from 7.1.18 to 7.1.19 (#362) (86331731)
  *  bump lint-staged from 12.3.6 to 12.3.7 (#358) (17794b26)
  *  bump simple-git from 3.3.0 to 3.4.0 (#356) (3333f402)
  *  bump @types/react from 17.0.40 to 17.0.41 (#357) (3a749b8a)
  *  bump prettier from 2.5.1 to 2.6.0 (#352) (f4125533)
  *  bump lint-staged from 12.3.5 to 12.3.6 (#351) (ef610456)
  *  bump eslint-config-shellscape from 4.2.0 to 5.0.2 (b862f86c)
  *  bump @typescript-eslint/parser from 5.14.0 to 5.15.0 (#350) (8585e7bb)
  *  bump @typescript-eslint/eslint-plugin (#349) (ee65aeba)
  *  bump @babel/core from 7.17.5 to 7.17.7 (#348) (1cfef93a)
  *  bump eslint from 8.10.0 to 8.11.0 (#346) (d45ae53b)
  *  bump simple-git from 3.2.6 to 3.3.0 (#347) (b35b47d1)
  *  bump eslint-plugin-react from 7.29.3 to 7.29.4 (#345) (931917c1)
  *  bump @types/react from 17.0.39 to 17.0.40 (#344) (c4e447c8)
  *  bump @types/isomorphic-fetch from 0.0.35 to 0.0.36 (#343) (95538189)
  *  bump @testing-library/react from 12.1.3 to 12.1.4 (#342) (18e536b7)
  *  bump @prettier/plugin-php from 0.18.2 to 0.18.3 (#341) (439caa42)
  *  bump css-loader from 6.7.0 to 6.7.1 (#340) (52dc21e1)
  *  bump @typescript-eslint/eslint-plugin (#339) (31973218)
  *  bump @typescript-eslint/parser from 5.13.0 to 5.14.0 (#338) (ae75ff56)
  *  bump lint-staged from 12.3.4 to 12.3.5 (#336) (7ad66930)
  *  bump css-loader from 6.6.0 to 6.7.0 (#335) (c447875d)
  *  bump webpack from 5.69.1 to 5.70.0 (#334) (0203a9ce)
  *  bump eslint-plugin-react from 7.29.2 to 7.29.3 (#333) (3250419c)
  *  bump @types/react-dom from 17.0.12 to 17.0.13 (#331) (15438b8d)
  *  bump eslint-config-prettier from 8.4.0 to 8.5.0 (#330) (a248ff78)
  *  bump @types/styled-components from 5.1.23 to 5.1.24 (#328) (f3096600)
  *  bump @types/testing-library__jest-dom (#327) (08c9338e)
  *  bump @types/react-dom from 17.0.11 to 17.0.12 (#326) (ad7fc21e)
  *  bump @typescript-eslint/parser from 5.12.1 to 5.13.0 (#322) (9a3c0e79)
  *  bump typescript from 4.5.5 to 4.6.2 (#324) (64ac48e8)
  *  bump @typescript-eslint/eslint-plugin (#323) (3ed14e46)
  *  bump eslint from 8.9.0 to 8.10.0 (#321) (3613117b)
  *  bump eslint-plugin-react from 7.28.0 to 7.29.2 (#320) (ff0ad71a)
  *  bump @types/jquery from 3.5.13 to 3.5.14 (#319) (36832a87)
  *  bump @typescript-eslint/eslint-plugin (#317) (3dbed879)
  *  bump @babel/cli from 7.17.3 to 7.17.6 (#318) (af070ede)
  *  bump @typescript-eslint/parser from 5.12.0 to 5.12.1 (#316) (1ce65677)
  *  bump eslint-config-prettier from 8.3.0 to 8.4.0 (#314) (31035245)
  *  bump @prettier/plugin-php from 0.18.1 to 0.18.2 (#313) (49f53909)
* **deps:**
  *  bump vite from 2.9.0 to 2.9.1 (#396) (d704dfc6)
  *  bump qrcode.react from 3.0.0 to 3.0.1 (#394) (f3115c3f)
  *  bump qrcode.react from 2.0.0 to 3.0.0 (1d4281d2)
  *  bump styled-components from 5.3.3 to 5.3.5 (#377) (78fa3e07)
  *  bump libphonenumber-js from 1.9.49 to 1.9.50 (#360) (b421baaf)
  *  bump qrcode.react from 1.0.1 to 2.0.0 (ea70bbc3)
  *  bump vite from 2.8.5 to 2.8.6 (#329) (d2fa6425)
  *  bump vite from 2.8.4 to 2.8.5 (#325) (3ee90300)
  *  bump vite from 2.8.3 to 2.8.4 (#312) (0ffaf660)

##### New Features

* **giftbackAppliedValue:**
  *  validate if giftback is greater than zero (281331e7)
  *  use the new field to create the coupon (5232e4f1)
* **debug:**  improving debug (3bcd69e8)
* **coupon-name:**  rename to giftback (09b6a12f)
* **config:**  config (3dd8eb53)
* **woo:**  remove hijack (9ce13224)
* **coupon:**  use float and round instead int to calculate the coupon value (226814d4)
* **envs:**
  *  add the right env of plugin on stg (14786c41)
  *  kill hardcoded openpix_env (5f6ccc70)
  *  set prod as default (31b97246)
  *  improve how we managment our envs (fbeb45dc)
* **pack:**
  *  add name to see the name on zip files (a1e694ce)
  *  improve script to pack the files (ba125555)
* **readme:**  readme update (ca9b53b0)

##### Bug Fixes

* **refresh:**  fix refresh (0a3db361)
* **url:**  fix wrong usage of the plugins_url (695f8e94)
* **modal:**  close when paying as guest (6183fd8c)

#### 2.0.3 (2022-02-18)

##### Build System / Dependencies

* **deps-dev:**
  *  bump simple-git from 3.2.4 to 3.2.6 (#308) (072c070c)
  *  bump webpack from 5.69.0 to 5.69.1 (#307) (630ec0c7)
  *  bump @types/styled-components from 5.1.22 to 5.1.23 (#306) (eff58927)
  *  bump @babel/core from 7.17.4 to 7.17.5 (#305) (af59d73e)
  *  bump @babel/core from 7.17.2 to 7.17.4 (#298) (431592d6)
  *  bump @testing-library/react from 12.1.2 to 12.1.3 (#299) (795ccbe4)
  *  bump @babel/cli from 7.17.0 to 7.17.3 (#297) (26242312)
  *  bump webpack from 5.68.0 to 5.69.0 (#295) (3e05efe3)
* **deps:**
  *  bump core-js from 3.21.0 to 3.21.1 (#301) (658a9b9d)
  *  bump vite from 2.8.2 to 2.8.3 (#296) (eb8ed476)

##### New Features

* **changelog:**  changelog (8f17928b)
* **tests:**  more tests on checkout (2dabc943)
* **infra:**  test like end user (51af06e2)
* **warning:**  remove warning (38d7aa83)
* **giftback:**  remove warning to access the array data (140749e3)
* **svn:**  svn release (fbb49010)
* **readme:**  readme (a8d98da1)
* **main:**  main instead of master (d84167e7)

##### Bug Fixes

* **checkout:**  add event pay as a guest (cb30413e)

#### 2.0.2 (2022-02-15)

##### Build System / Dependencies

* **deps-dev:**
  *  bump @typescript-eslint/parser from 5.11.0 to 5.12.0 (#288) (b7b5c972)
  *  bump @typescript-eslint/eslint-plugin (#287) (f0c016fd)
  *  bump @prettier/plugin-php from 0.18.0 to 0.18.1 (#282) (e4c51b67)
  *  bump lint-staged from 12.3.3 to 12.3.4 (#281) (3274d6eb)
  *  bump simple-git from 3.1.1 to 3.2.4 (#280) (ece3aad7)
  *  bump eslint from 8.8.0 to 8.9.0 (#279) (b7b86e18)
  *  bump npmlog from 6.0.0 to 6.0.1 (#275) (48746c17)
  *  bump @types/prettier from 2.4.3 to 2.4.4 (#274) (0c7c9ce2)
  *  bump babel-jest from 27.5.0 to 27.5.1 (#273) (f57fb435)
  *  bump jest from 27.5.0 to 27.5.1 (#272) (7851b425)
  *  bump @babel/core from 7.17.0 to 7.17.2 (#271) (704d5b7f)
  *  bump @typescript-eslint/eslint-plugin (#269) (6b1d1a23)
  *  bump @typescript-eslint/parser from 5.10.2 to 5.11.0 (#270) (ea8cd88b)
  *  bump jest from 27.4.7 to 27.5.0 (#268) (504570da)
  *  bump babel-jest from 27.4.6 to 27.5.0 (#266) (ee642231)
  *  bump @types/styled-components from 5.1.21 to 5.1.22 (#265) (3200fe0b)
  *  bump @prettier/plugin-php from 0.17.6 to 0.18.0 (#264) (731d119d)
  *  bump @types/react from 17.0.38 to 17.0.39 (#263) (6231f8a8)
  *  bump @babel/cli from 7.16.8 to 7.17.0 (#260) (928fc22c)
  *  bump @babel/core from 7.16.12 to 7.17.0 (#259) (0efe6736)
  *  bump @testing-library/jest-dom from 5.16.1 to 5.16.2 (#257) (23353cf5)
  *  bump @babel/plugin-transform-runtime (#258) (b80d3275)
  *  bump webpack-dev-server from 4.7.3 to 4.7.4 (#255) (4792a15f)
  *  bump css-loader from 6.5.1 to 6.6.0 (#256) (556ca78d)
  *  bump webpack-plugin-serve from 1.5.0 to 1.6.0 (#251) (d90525f8)
  *  bump lint-staged from 12.3.2 to 12.3.3 (#250) (4085ba0c)
  *  bump terser-webpack-plugin from 5.3.0 to 5.3.1 (#248) (357f3b48)
* **deps:**
  *  bump vite from 2.8.1 to 2.8.2 (#286) (b9a7519d)
  *  bump vite from 2.8.0 to 2.8.1 (#283) (65d47efc)
  *  bump libphonenumber-js from 1.9.48 to 1.9.49 (#278) (0e3cbdda)
  *  bump vite from 2.7.13 to 2.8.0 (#276) (1174c3a9)
  *  bump libphonenumber-js from 1.9.47 to 1.9.48 (#267) (a95e826b)
  *  bump core-js from 3.20.3 to 3.21.0 (#252) (9bcb812a)
  *  bump libphonenumber-js from 1.9.46 to 1.9.47 (#249) (9965126a)

##### New Features

* **update:**  update version using sed (3067e209)
* **checkout-plugin-mock-test:**  checkout test to validate openpix push call (#284) (f1ba2906)
* **rename-cashback-giftback:**  renaming to giftback (#277) (84679c00)
* **config:**  env local (90606b4a)

##### Bug Fixes

* **main:**  generate changelog using main (01e395b0)
* **customer:**  get customer from event complete (44c16166)
* **customer-by-shopper-taxid:**  fixing customer taxid field if shopper is logged in (#253) (b4187d9d)
* **tax-id:**  get taxid (57384479)

#### 2.0.1 (2022-02-01)

##### Build System / Dependencies

* **deps-dev:**
  *  bump @typescript-eslint/parser from 5.10.1 to 5.10.2 (#238) (2183180a)
  *  bump webpack from 5.67.0 to 5.68.0 (#240) (150adfe6)
  *  bump eslint-plugin-compat from 4.0.1 to 4.0.2 (#239) (abb975e2)
  *  bump @typescript-eslint/eslint-plugin (#237) (c1667197)

##### New Features

* **version:**  fix version (e4456bda)
* **pack:**  more stricti packing (78909262)
* **clean:**  cleanup (a84667b2)
* **cashback-complete-event:**  cashback complete event apply (#242) (450ba39c)
* **readonly:**  remove readonly fields (bf9ff84c)

##### Bug Fixes

* **pack:**  improve pack and add cashback includes (0fae416f)

## 2.0.0 (2022-01-31)

##### Build System / Dependencies

* **deps-dev:**
  *  bump eslint from 8.7.0 to 8.8.0 (#228) (23aad456)
  *  bump dotenv-webpack from 7.0.3 to 7.1.0 (#227) (ad7540cb)
  *  bump lint-staged from 12.3.1 to 12.3.2 (#223) (97b52152)
  *  bump simple-git from 3.1.0 to 3.1.1 (#221) (602cac8e)
  *  bump @testing-library/dom from 8.11.2 to 8.11.3 (#219) (acf6ea5e)
  *  bump @webpack-cli/serve from 1.6.0 to 1.6.1 (#216) (7d4d9706)
  *  bump @typescript-eslint/eslint-plugin (#214) (44de43f3)
  *  bump webpack-cli from 4.9.1 to 4.9.2 (#215) (52fa20a5)
  *  bump @typescript-eslint/parser from 5.10.0 to 5.10.1 (#213) (75e16a99)
  *  bump @types/eslint from 8.4.0 to 8.4.1 (#211) (dc87511c)
  *  bump webpack from 5.66.0 to 5.67.0 (#210) (1a6c93c2)
  *  bump @babel/core from 7.16.10 to 7.16.12 (#209) (985101f1)
  *  bump lint-staged from 12.2.2 to 12.3.1 (#207) (82856148)
  *  bump simple-git from 2.48.0 to 3.1.0 (715df2bc)
  *  bump @types/styled-components from 5.1.20 to 5.1.21 (#206) (55e84f24)
  *  bump @babel/preset-env from 7.16.10 to 7.16.11 (#205) (08df66cd)
  *  bump typescript from 4.5.4 to 4.5.5 (#204) (19964191)
  *  bump lint-staged from 12.2.1 to 12.2.2 (#203) (ab588ca5)
  *  bump @babel/preset-env from 7.16.8 to 7.16.10 (#200) (110d4549)
  *  bump @babel/core from 7.16.7 to 7.16.10 (#199) (74fabac2)
  *  bump @babel/plugin-transform-runtime (#198) (064e6d6e)
  *  bump @types/eslint from 8.2.2 to 8.4.0 (#197) (3f0fe461)
  *  bump lint-staged from 12.2.0 to 12.2.1 (#196) (7bc7372f)
  *  bump lint-staged from 12.1.7 to 12.2.0 (#194) (18eea494)
  *  bump @typescript-eslint/parser from 5.9.1 to 5.10.0 (#190) (0bbb0bc8)
  *  bump @typescript-eslint/eslint-plugin (#189) (4c09aaea)
  *  bump @testing-library/dom from 8.11.1 to 8.11.2 (#187) (2535a665)
  *  bump eslint from 8.6.0 to 8.7.0 (#185) (678d30c7)
  *  bump esbuild-register from 3.3.1 to 3.3.2 (#178) (35ef7136)
  *  bump webpack from 5.65.0 to 5.66.0 (#177) (52e817af)
  *  bump @types/prettier from 2.4.2 to 2.4.3 (#176) (812d7899)
  *  bump @types/styled-system from 5.1.14 to 5.1.15 (#173) (ed7177a8)
  *  bump webpack-dev-server from 4.7.2 to 4.7.3 (#175) (27148798)
  *  bump @types/jquery from 3.5.12 to 3.5.13 (#174) (38397a4a)
  *  bump @types/styled-components from 5.1.19 to 5.1.20 (#172) (bea8218f)
  *  bump @babel/preset-env from 7.16.7 to 7.16.8 (#171) (a49d1858)
  *  bump @babel/plugin-transform-runtime (#169) (78201e31)
  *  bump @typescript-eslint/parser from 5.9.0 to 5.9.1 (#168) (9dbfbf49)
  *  bump @types/jquery from 3.5.11 to 3.5.12 (#170) (2c26b7f0)
  *  bump @typescript-eslint/eslint-plugin (#166) (ca2114eb)
  *  bump @babel/plugin-transform-typescript (#165) (ae62e87c)
  *  bump @babel/cli from 7.16.7 to 7.16.8 (8e154442)
  *  bump lint-staged from 12.1.6 to 12.1.7 (#164) (2765c308)
  *  bump lint-staged from 12.1.5 to 12.1.6 (2ce03454)
  *  bump @types/eslint from 8.2.1 to 8.2.2 (#161) (3803ac15)
  *  bump eslint-plugin-compat from 4.0.0 to 4.0.1 (#160) (130128fa)
  *  bump @types/webpack-dev-server from 4.5.1 to 4.7.2 (#159) (61402c0e)
  *  bump jest from 27.4.6 to 27.4.7 (#158) (12af6d88)
  *  bump babel-jest from 27.4.5 to 27.4.6 (#157) (d7cc6bf8)
  *  bump jest from 27.4.5 to 27.4.6 (#156) (824fb79e)
  *  bump @typescript-eslint/eslint-plugin (#155) (14c6c820)
  *  bump @typescript-eslint/parser from 5.8.1 to 5.9.0 (#154) (18e6ab22)
  *  bump eslint from 8.5.0 to 8.6.0 (#153) (0487dac8)
  *  bump eslint-plugin-import from 2.25.3 to 2.25.4 (#151) (1baa0f5a)
  *  bump @types/babel__core from 7.1.17 to 7.1.18 (#150) (36459287)
  *  bump @types/webpack-dev-server from 4.5.0 to 4.5.1 (#149) (36c3b383)
  *  bump lint-staged from 12.1.4 to 12.1.5 (#148) (b496242f)
  *  bump @babel/preset-env from 7.16.5 to 7.16.7 (#145) (419d610e)
  *  bump @babel/plugin-proposal-export-namespace-from (#147) (ab41fd7f)
  *  bump @babel/plugin-proposal-optional-chaining (#142) (400d196e)
  *  bump @babel/plugin-proposal-export-default-from (#141) (05c1aa1f)
  *  bump @babel/core from 7.16.5 to 7.16.7 (#140) (b33f8c85)
  *  bump @babel/plugin-transform-react-jsx-source (#143) (bac89f28)
  *  bump @babel/preset-typescript from 7.16.5 to 7.16.7 (#144) (b24e975d)
  *  bump @babel/plugin-proposal-nullish-coalescing-operator (#135) (bdc5956e)
  *  bump @babel/preset-react from 7.16.5 to 7.16.7 (#138) (59421c9b)
  *  bump @babel/plugin-transform-runtime (#139) (10d57ed4)
  *  bump @babel/plugin-proposal-class-properties (#137) (6e888532)
  *  bump @babel/cli from 7.16.0 to 7.16.7 (#136) (64e69260)
  *  bump @babel/plugin-transform-typescript (#134) (f1c61bcd)
  *  bump webpack-dev-server from 4.7.1 to 4.7.2 (#133) (13d8f4d8)
  *  bump eslint-config-airbnb from 19.0.2 to 19.0.4 (#132) (afb8a2eb)
  *  bump @typescript-eslint/eslint-plugin (#128) (632ce5b2)
  *  bump @types/styled-components from 5.1.18 to 5.1.19 (#119) (ee6f0694)
  *  bump esbuild-register from 3.2.1 to 3.3.1 (#129) (bd2dba65)
  *  bump @typescript-eslint/parser from 5.8.0 to 5.8.1 (#127) (c92c4ef8)
  *  bump lint-staged from 12.1.3 to 12.1.4 (#125) (07c45a56)
  *  bump @types/styled-system from 5.1.13 to 5.1.14 (b1dec75b)
  *  bump @types/jquery from 3.5.10 to 3.5.11 (e5dabf23)
  *  bump @types/react from 17.0.37 to 17.0.38 (d79aa288)
  *  bump @types/webpack-plugin-serve from 1.4.1 to 1.4.2 (0192767e)
  *  bump @types/hard-source-webpack-plugin (fb7b40bc)
  *  bump @pmmmwh/react-refresh-webpack-plugin (32541e57)
  *  bump eslint-plugin-react from 7.27.1 to 7.28.0 (e3a47bce)
  *  bump webpack-dev-server from 4.6.0 to 4.7.1 (f59105c1)
* **deps:**
  *  bump libphonenumber-js from 1.9.44 to 1.9.46 (#222) (69fba5a4)
  *  bump vite from 2.7.12 to 2.7.13 (#195) (9a231f47)
  *  bump core-js from 3.20.2 to 3.20.3 (#186) (0f866719)
  *  bump vite from 2.7.10 to 2.7.12 (#184) (ad7cd769)
  *  bump core-js from 3.20.1 to 3.20.2 (#152) (f3d2dc11)
  *  bump vite from 2.7.9 to 2.7.10 (#146) (44f6331a)
  *  bump vite from 2.7.8 to 2.7.9 (#131) (fe871704)
  *  bump core-js from 3.20.0 to 3.20.1 (#130) (8b85c912)
  *  bump vite from 2.7.7 to 2.7.8 (#126) (83ed265f)
  *  bump vite from 2.7.4 to 2.7.7 (9004852c)

##### Chores

* **unused-code:**  remove commented code (f2b67aa9)

##### New Features

* **clean:**  cleanup old options (cab7ee14)
* **ignore:**  ignore production assets (d524f911)
* **prod:**
  *  improve prd release and wp (46eb2d57)
  *  rollback prod (c0ef146d)
* **checks:**  even more checks (a0024194)
* **strict:**  be more strict (d81bccb3)
* **test:**  simple test on checkout (4e90ca5f)
* **cashback-inactive-company:**  listen if cashback is inactive (#226) (7169e833)
* **growth:**
  *  reimplements growth cashback (f7ffe917)
  *  bring growth to pix plugin (ab374bf0)
* **unification-🤝:**  using only pay with pix plugin (#220) (273b4997)
* **unify:**  more unificatino (117c5775)
* **git:**  fix simple git (b2df5855)
* **get-customer-shopper:**  getting name, phone, email and taxid from shopper and adding to customer fields if not exists (#201) (4b258407)
* **cashback-coupon-apply:**  adding coupon on cashback checkout (#191) (e2b8e361)
* **cashback-detail-order-review:**  added cashback applied to order review (#188) (1eb21204)
* **cashback-apply:**
  *  removed unecessary print_r (41bb66d0)
  *  checkout (8c4fdc96)
  *  added cashback to a order (9419f68d)
* **main:**  main (86d09b1b)
* **cashback:**  more work on apply cashback (6e804e81)
* **update:**  update (36889b1d)
* **simplify:**  remove unused options (088d97f7)
* **inject:**  inject plugin js if beta flag is enabled (f48295cd)
* **plugin-js:**  inject js plugin on checkout (c8d647ce)
* **merchant-sale:**  see orders to apply cashback (cfd79b37)
* **orders:**
  *  create new plugin (30fda8fa)
  *  add order track (6a0a9443)
* **orderId:**  limit orderId on comment to maxlength 140 (ef1a4e29)
* **typo:**  typo (ed1dcce6)
* **install:**  installment instrumentsion plugin on readme (c15fc2fe)

##### Bug Fixes

* **click:**  use single click instead of two, fix entria/woovi#29536 (533e7177)
* **woocommerce-cashback-import:**  removing cashback import (#224) (628c0a9e)
* **import:**  remove import (57c4f808)
* **cashback-apply:**  avoid ui apply of cashback more than once (#192) (484bd604)
* **include:**  include the new class (7ff17fda)
* **cashback-value-exposed:**  using cashback exposed on event data instead of shop balance (#182) (f6b8234e)
* **flag:**  back the production flag (a33b0d68)

##### Other Changes

* Open-Pix/woocommerce-openpix into feature/reimplement-growth-cashback (a1d5f201)
* **plugin-js:**  remove beta flag of plugin (41085529)

##### Refactors

* **cashback:**
  *  stable plugin request (164c4601)
  *  simplify code (3bb1baff)

#### 1.11.1 (2021-11-11)

##### New Features

* **orderId:**  add orderId on comment (a7e8e6a3)

##### Bug Fixes

* **ersion:**  fix version (a32a5f5b)

#### 1.9.2 (2021-11-09)

##### New Features

* **readme:**  stable readme tag (0015c300)

##### Bug Fixes

* **status:**  change update_status to before a payment_complete (e6d9da81)

#### 1.9.1 (2021-11-08)

##### Build System / Dependencies

* **change-log:**
  *  v1.9.0 (93491eac)
  *  v1.8.1 (18b11623)
  *  v1.8.1 (26dba2a7)
  *  v1.8.1 (8fd3b658)

##### New Features

* **release:**  improve release (fe17bedc)
* **additionalInfo:**
  *  change order_id to Order (f55883ef)
  *  add order_id on additionalInformation (d53439f9)
* **installment:**  installment plugin (cc0fb31c)
* **webhookStatus:**  add webhookStatus field (bb88e2b3)
* **appID:**  improve AJAX of appID to customer (5f0c3958)
* **one-click-button:**  improve labels of one click button (7f4741e4)
* **authorization:**
  *  improve authorization validation (fa3718c4)
  *  get authorization by header or query string (56da9930)
* **checkout:**  add plugin js and improve correlationID (5c44b669)
* **charge:**  save pix key on meta data (004c002e)

##### Bug Fixes

* **protocol:**  remove every return of http (3cbbcc6f)
* **webhookStatus:**  fix correct label to webhookStatus (1eae0d53)
* **isActive:**  check if have some webhook active on api (9ef9792c)
* **errors:**  add suport to errors from api (b7beb7e1)
* **hasActiveWebhook:**  fix validation (40348f26)
* **one-click:**  small fixes (3ee1c0a9)

##### Other Changes

* **order:**  add order translation (4896d9d7)
* **one-click:**
  *  add english labels of webhook status (62b1fe58)
  *  add labels of webhook status (ed09f956)
  *  improve labels (5263b66c)
  *  resolve conflicts (58dbbe09)
  *  add new strings of webhook (784a5b84)
  *  add ajax refresh to hmac and webhook auth (3517da98)
  *  add button value (385937d8)
  *  improve json returns (aac13363)
  *  add appid validation (91116d1d)
  *  adjust title (ea7c338c)
  *  add edge case if webhook alredy configured (4a26f33d)
  *  prettier (0b56defa)
  *  add dinamic happy case scenario (5dbb68cf)
  *  add button to config webhook of customer (c065ff97)
* **authorization:**  add postman (9a3b7d24)
* **debug:**  remove debugs (7becfae8)

#### 1.9.0 (2021-10-29)

##### New Features

* **installment:**  installment plugin (cc0fb31c)
* **webhookStatus:**  add webhookStatus field (bb88e2b3)
* **appID:**  improve AJAX of appID to customer (5f0c3958)
* **one-click-button:** add webhook config one click button (7f4741e4)
* **authorization:**
  * improve authorization validation (fa3718c4)
  * get authorization by header or query string (56da9930)
* **checkout:**  add plugin js and improve correlationID (5c44b669)
* **charge:**  save pix key on meta data (004c002e)

### 1.8.0 (2021-09-23)

##### Chores

* **stuff:**  stuff (29828c74)

##### New Features

* **realtime:**  realtime flag (889d81bf)
* **upgrade:**  upgrade (fd49e41c)
* **order:**  success polling (c68b68ea)

##### Bug Fixes

* **i18n:**  i18n (b8f988f8)

### 1.7.0 (2021-09-21)

##### New Features

* **status:**
  * edit title of select (87bdf969)
  * add change status when paid (5b30a3a4)
* **remove:**  remove old approach (41a89a94)
* **stable:**  stable (2900287c)

##### Bug Fixes

* **logs:**  improve logs of creating pix (058358ad)
* **error:**  add logs and edit error message (11498e6c)
* **chargeCreate:**  remove dump of json error (f3dfc5dd)

##### Other Changes

* **pix:**  add i18n of error creating pix (0df0c360)
* **status:**  add status when paid i18n (031014b9)

#### 1.6.1 (2021-08-25)

##### New Features

* **header:**  add version and platform headers on plugin, see entria/feedback-server#25205 (1f371f14)

##### Bug Fixes

* **logs:**  remove logs (ccd3e838)
* **validate:**  improve validate on order data, fix entria/feedback-server#25290 (65bd1c51)

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
  * add 55 on phone number (44d636c2)
  * person type (b953f561)

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
  * fix version (533470ba)
  * fix version number on php (c5fd02a3)

### 1.1.0 (2021-05-27)

##### New Features

* **release:**  release automation (745adb4b)
* **i18n:**
  * translate plugin (010bb00c)
  * i18n (de779d00)
  * add translation to OpenPix plugin (20ae838f)
* **status:**  let user customize order status, make wc-pending default, fix entria/feedback-server#23022 (b035bd5c)
* **safe:**
  * safe cents on wp, fix entria/feedback-server#23006 (fadaeefe)
  * safe js (2c0fc50d)
* **v1:**
  * 1.0.1 (6462a732)
  * v1 (c0ae3471)
* **responsive:**  fix wwocommerce responsive fix entria/feedback-server#22703 (9cb10c54)
* **global:**  avoid global functions, prefer static functions instead (431c2862)
* **pack:**  remove backup po pot files (ecbd0f79)
* **sanitize:**  sanitize text fields and email, fix entria/feedback-server#21955 (a3828e9a)
* **translate:**  add mising i18n (13a58394)
* **ipn:**
  * show proper ipn to be registered at OpenPix (eb6cbbeb)
  * add ipn handler for webhook (97f72134)
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

* Robust float to cents logic
* Be able to customize order status after Pix is emitted

#### 1.0.1

* Responsive improvement

#### 1.0.0

* First version
