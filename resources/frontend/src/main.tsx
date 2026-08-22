import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.tsx'
import { BrowserRouter } from 'react-router-dom'
import { Provider } from 'react-redux'
import { store } from './store/store'
import { PersistGate } from 'redux-persist/integration/react';
import { persistStore } from 'redux-persist'
import { ThemeProvider } from './context/ThemeContext'

import './i18n'

const persist = persistStore(store)

createRoot(document.getElementById('root')!).render(
    <Provider store={store}>
        {/* <PersistGate persistor={persist}> */}
            <ThemeProvider>
                <BrowserRouter>
                    <App />
                </BrowserRouter>
            </ThemeProvider>
        {/* </PersistGate> */}
    </Provider>
)
