import { initializeApp, getApps, getApp } from "firebase/app";
import { getAuth, RecaptchaVerifier, signInWithPhoneNumber } from "firebase/auth";

const firebaseConfig = {
  apiKey: "AIzaSyBOxaCsebRyFLnMkI1OckcBSKR1tSHwIHA",
  authDomain: "nhaxekientruc.firebaseapp.com",
  projectId: "nhaxekientruc",
  storageBucket: "nhaxekientruc.firebasestorage.app",
  messagingSenderId: "187624154578",
  appId: "1:187624154578:web:f12788b7c93ce4b9d9140b",
  measurementId: "G-9Z5D4L534N"
};

const app = getApps().length === 0 ? initializeApp(firebaseConfig) : getApp();
const auth = getAuth(app);
auth.useDeviceLanguage();

export { app, auth, RecaptchaVerifier, signInWithPhoneNumber };
