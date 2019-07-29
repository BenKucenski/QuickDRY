using System;
using System.Collections;
using System.Collections.Generic;

namespace QuickDRY
{
    public class SafeClass
    {
        public void FromRow(Hashtable row)
        {
            // public string foo === field
            // publi string bar {get; set; } === property

            Dictionary<string, string> MissingColumns = new Dictionary<string, string>();

            foreach (string name in row.Keys)
            {
                if(name == "" || name == null)
                {
                    Log.Insert("SafeClass - Name is not given");
                    continue;
                }
                var value = row[name];
                switch (value.GetType().Name)
                {
                    case "Decimal":
                        value = Convert.ToDecimal(value);
                        break;
                    case "String":
                        break;
                    case "DateTime":
                        value = ((DateTime)value).ToString();
                        break;
                    case "Int32":
                        value = Convert.ToInt32(value);
                        break;
                    case "DBNull":
                        value = null;
                        break;
                    default:
                        Log.Insert("SafeClass - undefined Type: " + name + " " + value.GetType().Name);
                        continue;
                }

                var field = GetType().GetField(name);
                if (field == null)
                {
                    var prop = GetType().GetProperty(name);

                    if (prop == null)
                    {
                        MissingColumns.Add(name, name.GetType().Name);

                        Log.Insert("SafeClass - undefined Property: " + GetType().FullName + " Does Not Have Property " + name);
                        continue;
                    }
                    else
                    {
                        prop.SetValue(this, value);
                    }
                }
                else
                {
                    string ToType = field.FieldType.Name;
                       
                    switch (ToType)
                    {
                        case "Double":
                            value = Convert.ToDouble(value);
                            break;
                        case "Decimal":
                            value = Convert.ToDecimal(value);
                            break;
                        case "String":
                            value = Convert.ToString(value);
                            break;
                        case "Int32": // can't convert Decimal to Int32 implicitly
                            value = Convert.ToInt32(value);
                            break;
                        default:
                            Log.Insert(name + " Is Type " + ToType);
                            break;
                    }
                    field.SetValue(this, value);
                }
            }
            if(MissingColumns.Count > 0)
            {
                string code = "";
                foreach(string name in MissingColumns.Keys)
                {
                    code += "public " + MissingColumns[name] + " " + name + ";\r\n";
                }
                Debug.Halt(code);
            }
        }

        public SafeClass()
        {

        }
        public SafeClass(Hashtable row)
        {
            if (row == null)
            {
                return;
            }
            FromRow(row);
        }
    }
}
