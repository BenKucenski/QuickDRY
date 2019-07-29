using Newtonsoft.Json;
using System;

namespace QuickDRY
{
    public class Log
    {
        public static long StartTime;
        private static bool NoConsoleOutput = false;

        public struct MessageTime
        {
            public String given_msg;
            public String text;
            public double minutes;
        };

        public static MessageTime Insert(object msg)
        {
            if (StartTime == 0)
            {
                StartTime = Dates.UnixTimeSeconds();
            }
            long EndTime = Dates.UnixTimeSeconds();

            double minutes = Math.Round((EndTime - StartTime) / 60.0, 2);
            string text = Dates.Timestamp(null) + "\t" + Constants.Network + "\t" + Constants.GUID + "\t";
            text += minutes + "\t";

            string given_msg = msg.GetType().Name == "String" ? msg.ToString() : JsonConvert.SerializeObject(msg, Formatting.Indented);
            text += given_msg;
            if (!NoConsoleOutput)
            {
                Console.WriteLine(text);
            }
            MessageTime mt = new MessageTime();
            mt.given_msg = given_msg;
            mt.minutes = minutes;
            mt.text = text;
            return mt;
        }

        public static void Quiet(bool quiet)
        {
            NoConsoleOutput = quiet;
        }

    }
}
