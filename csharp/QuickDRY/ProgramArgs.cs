using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace QuickDRY
{
    public class ProgramArgs
    {
        public static string Errors;
        public static string ValueString;

        public static Dictionary<string, string> Parse(Dictionary<string, bool> Options, string[] args)
        {
            int RequiredParams = 0;
            Errors = "";
            ValueString = "";

            foreach (string name in Options.Keys)
            {
                if (Options[name])
                {
                    RequiredParams++;
                }
            }
            if (args.Length < RequiredParams)
            {
                Errors += "Missing Command Line Parameters\n";
                return null;
            }

            Dictionary<string, string> NamedArgs = new Dictionary<string, string>();
            foreach (string arg in args)
            {
                if (arg.Length < 2)
                {
                    Errors += "Parameter " + arg + " must be preceded by a -\n";
                    return null;
                }

                if (arg[0] != '-')
                {
                    Log.Insert("Parameter " + arg + " must be preceded by a -");
                    return null;
                }

                string name = (Convert.ToString(arg[1])).ToLower();
                string value = (arg.Substring(2, arg.Length - 2)).Replace("\"", "");
                NamedArgs[name] = value;
            }

            foreach (string name in Options.Keys)
            {
                if (!Options[name])
                {
                    if (!NamedArgs.ContainsKey(name))
                    {
                        NamedArgs[name] = "";
                    }
                }
                else
                {
                    if (!NamedArgs.ContainsKey(name))
                    {
                        Errors += "Parameter -" + name + " is missing";
                        return null;
                    }
                }

                if (NamedArgs[name] != "")
                {
                    ValueString += name + ": " + NamedArgs[name] + "\n";
                }
            }


            return NamedArgs;
        }
    }
}
