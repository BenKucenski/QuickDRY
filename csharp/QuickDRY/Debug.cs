using System;
using System.Web;
using Newtonsoft.Json;

// Tools -> NuGet Package Manager -> Package Manager Console -> PM> Install-Package Newtonsoft.Json
namespace QuickDRY
{
    public class Debug
    {
        public Debug() { }

        public static bool IsIIS()
        {
            try
            {
                System.Web.HttpContext context = System.Web.HttpContext.Current;
                string test = context.Request.ServerVariables["REMOTE_ADDR"];
            }
            catch (Exception ex)
            {
                return false;
            }
            return true;
        }

        public static string Halt(object message)
        {
            return Halt(message, true, true);
        }

        public static string Halt(object message, bool SendEmail, bool InsertLog)
        {
            // Usage: in aspx.cs code
            // using QuickDry;
            // Response.Write(Debug.Halt(passport));
            // Response.End();
            string msg = JsonConvert.SerializeObject(message, Formatting.Indented);
            msg = msg.Replace("\\r\\n", "<br/>");
            msg = msg.Replace("\\t", "    ");
            msg = "Debug Halt\r\n" + msg + "\r\n" + String.Format("StackTrace: '{0}'", Environment.StackTrace);

            if (SendEmail)
            {
                Mailer email = new Mailer(
                    Constants.EMAIL_HALT_EMAIL,
                    Constants.EMAIL_HALT_NAME,
                    Constants.EMAIL_HALT_SUBJECT + ": Halt Encountered",
                    "<pre>" + msg + "</pre><br/><br/><pre>" + Environment.StackTrace + "</pre>",
                    Constants.EMAIL_HALT_FROM_EMAIL,
                    Constants.EMAIL_HALT_FROM_EMAIL
                );

                email.Send();
            }
            if(InsertLog)
            {
                DBLog.Log(Log.Insert("Debug Halt\r\n" + msg + "\r\n" + string.Format("StackTrace: '{0}'", Environment.StackTrace)));
            }

            if (IsIIS())
            {
                return "<pre>" + msg + "</pre>";
            }
            Console.WriteLine(msg);
            Environment.Exit(0); // don't do this in IIS, it will take down the app pool
            return ""; // won't actually be reached but Visual Studio doesn't know that

        }

        public static string ExitJSON(object message)
        {
            string msg = JsonConvert.SerializeObject(message, Formatting.Indented);
            if (IsIIS())
            {
                return msg;
            }
            Console.WriteLine(msg);
            Environment.Exit(0); // don't do this in IIS, it will take down the app pool
            return ""; // won't actually be reached but Visual Studio doesn't know that
        }

        public static string GetIPAddress()
        {
            System.Web.HttpContext context = System.Web.HttpContext.Current;
            string ipAddress = context.Request.ServerVariables["HTTP_X_FORWARDED_FOR"];

            if (!string.IsNullOrEmpty(ipAddress))
            {
                string[] addresses = ipAddress.Split(',');
                if (addresses.Length != 0)
                {
                    return addresses[0];
                }
            }

            return context.Request.ServerVariables["REMOTE_ADDR"];
        }

        public static string SessionContent()
        {
            string str = null;
            foreach (string key in HttpContext.Current.Session.Keys)
            {
                str += string.Format("{0}: {1}<br />", key, HttpContext.Current.Session[key].ToString());
            }
            return str;
        }
    }
}
