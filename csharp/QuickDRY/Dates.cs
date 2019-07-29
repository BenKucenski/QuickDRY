using System;

namespace QuickDRY
{
    public class Dates
    {
        public static long UnixTimeSeconds()
        {
            // https://stackoverflow.com/questions/17632584/how-to-get-the-unix-timestamp-in-c-sharp
            DateTime foo = DateTime.UtcNow;
            return ((DateTimeOffset)foo).ToUnixTimeSeconds();
        }

        public static string Timestamp(DateTime? dt)
        {
            dt = dt.HasValue ? dt.Value : DateTime.Now;
            return dt.ToString();
        }

        public static string FancyDateTime(DateTime? dt)
        {
            dt = dt.HasValue ? dt.Value : DateTime.Now;
            return dt.ToString();
        }

        public static string FancyDate(DateTime? dt)
        {
            // https://www.c-sharpcorner.com/blogs/date-and-time-format-in-c-sharp-programming1
            dt = dt.HasValue ? dt.Value : DateTime.Now;
            return dt.Value.ToString("dddd, MMMM dd, yyyy");
        }
    }
}
