import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
    plugins: [
    ],
    resolve:{
        alias:{
            '~bootstrap':path.resolve(__dirname,'node_modules/bootstrap')
        }
    },
    build:{
        lib:{
            name: 'Pubi',
            entry: [
                path.resolve(__dirname,'main.js'),
                // path.resolve(__dirname,'guest.js')
            ],
            fileName: (format, entryAlias) => {


                console.log(format, entryAlias)
                return `${entryAlias}.js`
            },
            formats: ['es'],
        },
        outDir: path.resolve(__dirname,'assets')
    }
});
