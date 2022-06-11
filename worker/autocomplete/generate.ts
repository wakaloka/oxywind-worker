import { promises as fs } from 'fs';
import vm from 'vm';
import path from 'path';
import _ from 'lodash';
import { TTailwindCSSConfig } from 'tailwindcss-classnames/lib/cli/types/config';
import { TAllClassnames } from 'tailwindcss-classnames/lib/cli/types/classes';
import { ClassnamesGenerator } from 'tailwindcss-classnames/lib/cli/core/ClassnamesGenerator';
import { TailwindConfigParser } from 'tailwindcss-classnames/lib/cli/core/TailwindConfigParser';

class GeneratedFileWriter {
    /** The data returned from reading the config file */
    private _configFileData = '';

    /**
   * Writes the generated file to disk.
   */
    public write = async (): Promise<void> => {

        // If the inputs were valid, generate the file content
        const contentGenerationResult = await this.generateFileContent();

        // Then write the generation result to a file with the provided value from the CLI interface.
        fs.writeFile(`autocomplete.json`, contentGenerationResult, 'utf8')
            .then(() => {
                console.log(`Successfully generated autocomplete.json file`);
            })
            .catch(err => {
                throw new Error(`Failed writing the autocomplete.json file ${err.message}`);
            });
    };

    private evaluateTailwindConfigFile = async (): Promise<TTailwindCSSConfig> => {
        // Read the config file from the provided config path
        try {
            this._configFileData = await fs.readFile(`./tailwind.config.js`, { encoding: 'utf-8' });
        } catch (e: any) {
            throw Error(`Failed reading the config file: ${e.message}`);
        }

        // Execute the config file content as JavaScript code
        return <TTailwindCSSConfig>vm.runInNewContext(this._configFileData, {
            __dirname: path.dirname(path.resolve(`./tailwind.config.js`)),
            require,
            module: {},
            process,
        });
    };

    private generateFileContent = async (): Promise<string> => {
        // Evaluate the config as a JS object
        const evaluatedConfig = await this.evaluateTailwindConfigFile();

        // Parse the config with the config parser class
        const configParser = new TailwindConfigParser(evaluatedConfig, {
            pluginTypography: this._configFileData.includes('@tailwindcss/typography'),
            pluginCustomForms: this._configFileData.includes('@tailwindcss/forms'),
        });

        // Generate all classnames from the config
        const generatedClassnames = new ClassnamesGenerator(configParser).generate();

        // Create the file content from the generated classnames
        const regularClasses = Object.keys(generatedClassnames)
            .flatMap(classGroupKey => {
                const group = generatedClassnames[classGroupKey as keyof TAllClassnames] as TAllClassnames;

                const members = Object.keys(group);

                return members.flatMap(member => {
                    return JSON.parse(JSON.stringify(group[member as keyof TAllClassnames]));
                });

            });

        let variantClasses = configParser.getVariants();

        variantClasses.push('dark');

        variantClasses = variantClasses.map(variant => variant + configParser.getSeparator());

        let content = [
            ...regularClasses,
            ...variantClasses,
        ];

        // Return final file content
        return JSON.stringify(_.uniq(content));
    };
}

(async () => {
    await new GeneratedFileWriter().write()
})();