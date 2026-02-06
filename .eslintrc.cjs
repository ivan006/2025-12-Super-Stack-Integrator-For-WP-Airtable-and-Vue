module.exports = {
  root: true,

  parser: "vue-eslint-parser", // Use the Vue ESLint parser

  parserOptions: {
    parser: "@babel/eslint-parser",
    requireConfigFile: false,
    babelOptions: {
      presets: ["@babel/preset-env"],
    },
    ecmaVersion: 2021,
    sourceType: "module",
  },

  env: {
    node: true,
    browser: true,
  },

  extends: [
    "plugin:vue/vue3-essential", // Priority A: Essential (Error Prevention)
    "prettier",
  ],

  plugins: ["vue"],

  globals: {
    ga: "readonly",
    cordova: "readonly",
    __statics: "readonly",
    __QUASAR_SSR__: "readonly",
    __QUASAR_SSR_SERVER__: "readonly",
    __QUASAR_SSR_CLIENT__: "readonly",
    __QUASAR_SSR_PWA__: "readonly",
    process: "readonly",
    Capacitor: "readonly",
    chrome: "readonly",
  },

  rules: {
    "prefer-promise-reject-errors": "off",
    "no-debugger": process.env.NODE_ENV === "production" ? "error" : "off",
  },
};
